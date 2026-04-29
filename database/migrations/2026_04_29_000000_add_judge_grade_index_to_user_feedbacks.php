<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_feedbacks', function (Blueprint $table) {
            // Speeds up "pairs judged" count and "claimed ungraded" lookups on
            // the Judges page. The single-column judge_id index forces MySQL
            // to fetch every row to evaluate the grade NULL check; a composite
            // (judge_id, grade) index turns the count into an index-only scan.
            $table->index(['judge_id', 'grade'], 'user_feedbacks_judge_id_grade_index');
        });
    }

    public function down(): void
    {
        Schema::table('user_feedbacks', function (Blueprint $table) {
            $table->dropIndex('user_feedbacks_judge_id_grade_index');
        });
    }
};
