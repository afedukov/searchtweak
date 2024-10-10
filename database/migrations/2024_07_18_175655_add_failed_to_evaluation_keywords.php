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
            $table->boolean(EvaluationKeyword::FIELD_FAILED)->nullable()->after(EvaluationKeyword::FIELD_EXECUTION_CODE);
        });

        $this->migrate();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evaluation_keywords', function (Blueprint $table) {
            $table->dropColumn(EvaluationKeyword::FIELD_FAILED);
        });
    }

    private function migrate(): void
    {
        EvaluationKeyword::query()
            ->each(function (EvaluationKeyword $keyword) {
                $keyword->failed = $keyword->isFailed();
                $keyword->save();
            });
    }
};
