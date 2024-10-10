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
        Schema::table('search_endpoints', function (Blueprint $table) {
            $table->json(SearchEndpoint::FIELD_SETTINGS)->nullable()->after(SearchEndpoint::FIELD_MAPPER_CODE);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('search_endpoints', function (Blueprint $table) {
            $table->dropColumn(SearchEndpoint::FIELD_SETTINGS);
        });
    }
};
