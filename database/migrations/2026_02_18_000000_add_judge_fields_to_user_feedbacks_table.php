<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_feedbacks', function (Blueprint $table) {
            $table->foreignId('judge_id')
                ->nullable()
                ->after('user_id')
                ->constrained('judges')
                ->nullOnDelete();

            $table->text('reason')
                ->nullable()
                ->after('grade');
        });
    }

    public function down(): void
    {
        Schema::table('user_feedbacks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('judge_id');
            $table->dropColumn('reason');
        });
    }
};
