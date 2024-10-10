<?php

use App\Models\EvaluationKeyword;
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
        Schema::create('evaluation_keywords', function (Blueprint $table) {
            $table->id();
            $table->foreignId(EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID)->constrained()->cascadeOnDelete();
            $table->string(EvaluationKeyword::FIELD_KEYWORD);
            $table->integer(EvaluationKeyword::FIELD_TOTAL_COUNT)->nullable();
            $table->integer(EvaluationKeyword::FIELD_EXECUTION_CODE)->nullable();
            $table->string(EvaluationKeyword::FIELD_EXECUTION_MESSAGE)->nullable();
            $table->timestamps();

            $table->unique([EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID, EvaluationKeyword::FIELD_KEYWORD], 'evaluation_keyword_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_keywords');
    }
};
