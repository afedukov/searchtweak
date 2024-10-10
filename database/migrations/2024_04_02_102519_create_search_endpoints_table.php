<?php

use App\Models\SearchEndpoint;
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
        Schema::create('search_endpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId(SearchEndpoint::FIELD_USER_ID)->constrained()->cascadeOnDelete();
            $table->foreignId(SearchEndpoint::FIELD_TEAM_ID)->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger(SearchEndpoint::FIELD_TYPE)->default(SearchEndpoint::TYPE_SEARCH_API);
            $table->string(SearchEndpoint::FIELD_NAME);
            $table->text(SearchEndpoint::FIELD_URL);
            $table->string(SearchEndpoint::FIELD_METHOD);
            $table->string(SearchEndpoint::FIELD_DESCRIPTION);
            $table->json(SearchEndpoint::FIELD_HEADERS);
            $table->unsignedTinyInteger(SearchEndpoint::FIELD_MAPPER_TYPE)->default(SearchEndpoint::MAPPER_TYPE_DOT_ARRAY);
            $table->text(SearchEndpoint::FIELD_MAPPER_CODE);
            $table->timestamp(SearchEndpoint::FIELD_ARCHIVED_AT)->nullable();
            $table->timestamps();

            $table->unique([SearchEndpoint::FIELD_TEAM_ID, SearchEndpoint::FIELD_NAME]);
            $table->index([SearchEndpoint::FIELD_TEAM_ID, SearchEndpoint::FIELD_ARCHIVED_AT]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_endpoints');
    }
};
