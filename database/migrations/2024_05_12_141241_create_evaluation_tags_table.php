<?php

use App\Models\EvaluationTag;
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
        Schema::create('evaluation_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId(EvaluationTag::FIELD_EVALUATION_ID)->constrained('search_evaluations')->cascadeOnDelete();
            $table->foreignId(EvaluationTag::FIELD_TAG_ID)->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique([EvaluationTag::FIELD_EVALUATION_ID, EvaluationTag::FIELD_TAG_ID]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_tags');
    }
};
