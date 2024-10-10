<?php

use App\Models\EvaluationMetric;
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
        Schema::create('evaluation_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId(EvaluationMetric::FIELD_SEARCH_EVALUATION_ID)->constrained()->cascadeOnDelete();
            $table->string(EvaluationMetric::FIELD_SCORER_TYPE, 32);
            $table->integer(EvaluationMetric::FIELD_NUM_RESULTS);
            $table->float(EvaluationMetric::FIELD_VALUE)->nullable();
            $table->json(EvaluationMetric::FIELD_SETTINGS);
            $table->timestamps();

            $table->unique([
                EvaluationMetric::FIELD_SEARCH_EVALUATION_ID,
                EvaluationMetric::FIELD_SCORER_TYPE,
                EvaluationMetric::FIELD_NUM_RESULTS,
            ], 'unique_evaluation_metrics');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_metrics');
    }
};
