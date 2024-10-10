<?php

use App\Models\EvaluationKeyword;
use App\Models\SearchEvaluation;
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
        Schema::table('search_evaluations', function (Blueprint $table) {
            $table->unsignedInteger(SearchEvaluation::FIELD_SUCCESSFUL_KEYWORDS)->nullable()->after(SearchEvaluation::FIELD_MAX_NUM_RESULTS);
            $table->unsignedInteger(SearchEvaluation::FIELD_FAILED_KEYWORDS)->nullable()->after(SearchEvaluation::FIELD_SUCCESSFUL_KEYWORDS);
        });

        $this->migrate();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('search_evaluations', function (Blueprint $table) {
            $table->dropColumn(SearchEvaluation::FIELD_SUCCESSFUL_KEYWORDS);
            $table->dropColumn(SearchEvaluation::FIELD_FAILED_KEYWORDS);
        });
    }

    /**
     * Migrate the data.
     */
    private function migrate(): void
    {
        SearchEvaluation::query()
            ->each(function (SearchEvaluation $evaluation) {
                $evaluation->update([
                    SearchEvaluation::FIELD_SUCCESSFUL_KEYWORDS => $evaluation->keywords()->where(EvaluationKeyword::FIELD_FAILED, false)->count(),
                    SearchEvaluation::FIELD_FAILED_KEYWORDS => $evaluation->keywords()->where(EvaluationKeyword::FIELD_FAILED, true)->count(),
                ]);
            });
    }
};
