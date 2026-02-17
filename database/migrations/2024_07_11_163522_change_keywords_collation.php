<?php

use App\Models\EvaluationKeyword;
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
        // SQLite doesn't support changing column collation on the fly,
        // and tests use SQLite in memory. This is safe to skip for tests.
        if (config('database.default') === 'sqlite') {
            return;
        }

        Schema::table('evaluation_keywords', function (Blueprint $table) {
            $table->string(EvaluationKeyword::FIELD_KEYWORD, 255)->collation('utf8mb4_bin')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (config('database.default') === 'sqlite') {
            return;
        }

        Schema::table('evaluation_keywords', function (Blueprint $table) {
            $table->string(EvaluationKeyword::FIELD_KEYWORD, 255)->collation('utf8mb4_unicode_ci')->change();
        });
    }
};
