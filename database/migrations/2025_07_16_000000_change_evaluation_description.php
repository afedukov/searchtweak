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
            $table->text(SearchEvaluation::FIELD_DESCRIPTION)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('search_evaluations', function (Blueprint $table) {
            $table->string(SearchEvaluation::FIELD_DESCRIPTION)->change();
        });
    }
};
