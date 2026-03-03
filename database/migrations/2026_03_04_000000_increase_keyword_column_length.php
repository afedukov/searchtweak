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
        if (config('database.default') === 'sqlite') {
            return;
        }

        Schema::table('evaluation_keywords', function (Blueprint $table) {
            $table->string(EvaluationKeyword::FIELD_KEYWORD, 500)->collation('utf8mb4_bin')->change();
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
            $table->string(EvaluationKeyword::FIELD_KEYWORD, 255)->collation('utf8mb4_bin')->change();
        });
    }
};
