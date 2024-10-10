<?php

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
        Schema::create('search_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId(SearchEvaluation::FIELD_USER_ID)->constrained()->cascadeOnDelete();
            $table->foreignId(SearchEvaluation::FIELD_MODEL_ID)->constrained('search_models')->cascadeOnDelete();
            $table->string(SearchEvaluation::FIELD_SCALE_TYPE, 32);
            $table->unsignedTinyInteger(SearchEvaluation::FIELD_STATUS)->default(SearchEvaluation::STATUS_PENDING);
            $table->unsignedTinyInteger(SearchEvaluation::FIELD_PROGRESS)->default(0);
            $table->string(SearchEvaluation::FIELD_NAME);
            $table->string(SearchEvaluation::FIELD_DESCRIPTION);
            $table->json(SearchEvaluation::FIELD_SETTINGS);
            $table->integer(SearchEvaluation::FIELD_MAX_NUM_RESULTS)->nullable();
            $table->timestamp(SearchEvaluation::FIELD_FINISHED_AT)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_evaluations');
    }
};
