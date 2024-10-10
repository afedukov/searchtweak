<?php

use App\Models\SearchSnapshot;
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
        Schema::create('search_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId(SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID)->constrained()->cascadeOnDelete();
            $table->integer(SearchSnapshot::FIELD_POSITION);
            $table->string(SearchSnapshot::FIELD_DOC_ID);
            $table->string(SearchSnapshot::FIELD_NAME, 2048);
            $table->string(SearchSnapshot::FIELD_IMAGE, 2048)->nullable();
            $table->json(SearchSnapshot::FIELD_DOC);
            $table->timestamps();

            $table->unique([SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID, SearchSnapshot::FIELD_POSITION], 'keyword_position_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_snapshots');
    }
};
