<?php

use App\Models\Judge;
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
        Schema::create('judges', function (Blueprint $table) {
            $table->id();
            $table->foreignId(Judge::FIELD_USER_ID)->constrained()->cascadeOnDelete();
            $table->foreignId(Judge::FIELD_TEAM_ID)->constrained()->cascadeOnDelete();
            $table->string(Judge::FIELD_NAME);
            $table->string(Judge::FIELD_DESCRIPTION)->nullable();
            $table->string(Judge::FIELD_PROVIDER);
            $table->string(Judge::FIELD_MODEL_NAME);
            $table->text(Judge::FIELD_API_KEY);
            $table->text(Judge::FIELD_PROMPT_BINARY)->nullable();
            $table->text(Judge::FIELD_PROMPT_GRADED)->nullable();
            $table->text(Judge::FIELD_PROMPT_DETAIL)->nullable();
            $table->json(Judge::FIELD_SETTINGS)->nullable();
            $table->timestamp(Judge::FIELD_ARCHIVED_AT)->nullable();
            $table->timestamps();

            $table->unique([Judge::FIELD_TEAM_ID, Judge::FIELD_NAME]);
            $table->index([Judge::FIELD_TEAM_ID, Judge::FIELD_ARCHIVED_AT]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('judges');
    }
};
