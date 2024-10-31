<?php

use App\Models\EvaluationMetric;
use App\Models\SearchEvaluation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('evaluation_metrics', function (Blueprint $table) {
            $table->float(EvaluationMetric::FIELD_PREVIOUS_VALUE)->nullable()->after(EvaluationMetric::FIELD_VALUE);
            $table->timestamp(EvaluationMetric::FIELD_FINISHED_AT)->nullable()->after(EvaluationMetric::FIELD_SETTINGS);
        });

        $this->migrateFinishedAt();
        $this->migratePreviousValue();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evaluation_metrics', function (Blueprint $table) {
            $table->dropColumn(EvaluationMetric::FIELD_PREVIOUS_VALUE);
            $table->dropColumn(EvaluationMetric::FIELD_FINISHED_AT);
        });
    }

    private function migrateFinishedAt(): void
    {
        EvaluationMetric::query()
            ->with('evaluation')
            ->whereNull(EvaluationMetric::FIELD_FINISHED_AT)
            ->whereHas('evaluation', fn (Builder $query) =>
                $query->where(SearchEvaluation::FIELD_STATUS, SearchEvaluation::STATUS_FINISHED)
            )
            ->get()
            ->each(function (EvaluationMetric $metric) {
                $metric->finished_at = $metric->evaluation->finished_at;
                $metric->saveQuietly();
            });
    }

    private function migratePreviousValue(): void
    {
        EvaluationMetric::query()
            ->with('evaluation')
            ->orderBy(EvaluationMetric::FIELD_ID)
            ->get()
            ->each(fn (EvaluationMetric $metric) => $metric->syncPreviousValue());
    }
};
