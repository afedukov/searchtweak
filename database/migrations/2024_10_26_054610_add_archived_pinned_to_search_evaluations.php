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
        Schema::table('search_evaluations', function (Blueprint $table) {
            $table->boolean(SearchEvaluation::FIELD_ARCHIVED)->default(false)->after(SearchEvaluation::FIELD_FAILED_KEYWORDS);
            $table->boolean(SearchEvaluation::FIELD_PINNED)->default(false)->after(SearchEvaluation::FIELD_ARCHIVED);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('search_evaluations', function (Blueprint $table) {
            $table->dropColumn([
                SearchEvaluation::FIELD_ARCHIVED,
                SearchEvaluation::FIELD_PINNED,
            ]);
        });
    }
};
