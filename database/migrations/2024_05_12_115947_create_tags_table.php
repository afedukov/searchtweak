<?php

use App\Models\Tag;
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
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId(Tag::FIELD_TEAM_ID)->constrained()->cascadeOnDelete();
            $table->string(Tag::FIELD_COLOR);
            $table->string(Tag::FIELD_NAME);
            $table->timestamps();

            $table->unique([Tag::FIELD_TEAM_ID, Tag::FIELD_COLOR, Tag::FIELD_NAME], 'unique_tags');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
