<?php

use App\Models\KeywordMetric;
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
        Schema::create('keyword_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId(KeywordMetric::FIELD_EVALUATION_KEYWORD_ID)->constrained()->cascadeOnDelete();
            $table->foreignId(KeywordMetric::FIELD_EVALUATION_METRIC_ID)->constrained()->cascadeOnDelete();
            $table->float(KeywordMetric::FIELD_VALUE)->nullable();
            $table->timestamps();

            $table->unique([
                KeywordMetric::FIELD_EVALUATION_KEYWORD_ID,
                KeywordMetric::FIELD_EVALUATION_METRIC_ID,
            ], 'keyword_metric_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keyword_metrics');
    }
};
