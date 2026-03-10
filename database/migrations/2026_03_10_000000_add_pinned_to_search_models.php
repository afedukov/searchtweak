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
            $table->boolean(SearchModel::FIELD_PINNED)->default(false)->after(SearchModel::FIELD_SETTINGS);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('search_models', function (Blueprint $table) {
            $table->dropColumn(SearchModel::FIELD_PINNED);
        });
    }
};
