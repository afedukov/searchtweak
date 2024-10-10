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
            $table->float(SearchEvaluation::FIELD_PROGRESS)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('search_evaluations', function (Blueprint $table) {
            $table->unsignedTinyInteger(SearchEvaluation::FIELD_PROGRESS)->default(0)->change();
        });
    }
};
