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
        Schema::table('evaluation_keywords', function (Blueprint $table) {
            $table->string(EvaluationKeyword::FIELD_EXECUTION_MESSAGE, 512)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evaluation_keywords', function (Blueprint $table) {
            $table->string(EvaluationKeyword::FIELD_EXECUTION_MESSAGE)->nullable()->change();
        });
    }
};
