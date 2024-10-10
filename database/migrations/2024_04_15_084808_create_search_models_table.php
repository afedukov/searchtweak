<?php

use App\Models\SearchModel;
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
        Schema::create('search_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId(SearchModel::FIELD_USER_ID)->constrained()->cascadeOnDelete();
            $table->foreignId(SearchModel::FIELD_TEAM_ID)->constrained()->cascadeOnDelete();
            $table->foreignId(SearchModel::FIELD_ENDPOINT_ID)->constrained('search_endpoints')->cascadeOnDelete();
            $table->string(SearchModel::FIELD_NAME);
            $table->string(SearchModel::FIELD_DESCRIPTION);
            $table->json(SearchModel::FIELD_HEADERS);
            $table->json(SearchModel::FIELD_PARAMS);
            $table->text(SearchModel::FIELD_BODY);
            $table->unsignedTinyInteger(SearchModel::FIELD_BODY_TYPE)->nullable();
            $table->timestamps();

            $table->unique([SearchModel::FIELD_TEAM_ID, SearchModel::FIELD_NAME]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_models');
    }
};
