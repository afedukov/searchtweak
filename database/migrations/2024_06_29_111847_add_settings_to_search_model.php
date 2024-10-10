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
        Schema::table('search_models', function (Blueprint $table) {
            $table->json(SearchModel::FIELD_SETTINGS)->after(SearchModel::FIELD_BODY_TYPE)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('search_models', function (Blueprint $table) {
            $table->dropColumn(SearchModel::FIELD_SETTINGS);
        });
    }
};
