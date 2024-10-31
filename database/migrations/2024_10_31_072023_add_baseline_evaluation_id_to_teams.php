<?php

use App\Models\Team;
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
        Schema::table('teams', function (Blueprint $table) {
            $table->foreignId(Team::FIELD_BASELINE_EVALUATION_ID)
                ->nullable()
                ->after(Team::FIELD_PERSONAL_TEAM)
                ->constrained('search_evaluations')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn(Team::FIELD_BASELINE_EVALUATION_ID);
        });
    }
};
