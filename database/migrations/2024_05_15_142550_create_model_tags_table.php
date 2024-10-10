<?php

use App\Models\ModelTag;
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
        Schema::create('model_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId(ModelTag::FIELD_MODEL_ID)->constrained('search_models')->cascadeOnDelete();
            $table->foreignId(ModelTag::FIELD_TAG_ID)->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique([ModelTag::FIELD_MODEL_ID, ModelTag::FIELD_TAG_ID]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model_tags');
    }
};
