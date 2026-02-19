<?php

use App\Models\JudgeLog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('judge_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId(JudgeLog::FIELD_JUDGE_ID)
                ->nullable()
                ->constrained('judges')
                ->nullOnDelete();
            $table->foreignId(JudgeLog::FIELD_TEAM_ID)
                ->nullable()
                ->constrained('teams')
                ->nullOnDelete();
            $table->foreignId(JudgeLog::FIELD_SEARCH_EVALUATION_ID)
                ->nullable()
                ->constrained('search_evaluations')
                ->nullOnDelete();
            $table->string(JudgeLog::FIELD_PROVIDER, 50);
            $table->string(JudgeLog::FIELD_MODEL);
            $table->smallInteger(JudgeLog::FIELD_HTTP_STATUS_CODE)->nullable();
            $table->text(JudgeLog::FIELD_REQUEST_URL);
            $table->longText(JudgeLog::FIELD_REQUEST_BODY);
            $table->longText(JudgeLog::FIELD_RESPONSE_BODY)->nullable();
            $table->text(JudgeLog::FIELD_ERROR_MESSAGE)->nullable();
            $table->integer(JudgeLog::FIELD_LATENCY_MS)->nullable();
            $table->integer(JudgeLog::FIELD_PROMPT_TOKENS)->nullable();
            $table->integer(JudgeLog::FIELD_COMPLETION_TOKENS)->nullable();
            $table->integer(JudgeLog::FIELD_TOTAL_TOKENS)->nullable();
            $table->tinyInteger(JudgeLog::FIELD_BATCH_SIZE)->nullable();
            $table->string(JudgeLog::FIELD_SCALE_TYPE, 20)->nullable();
            $table->timestamps();

            $table->index([JudgeLog::FIELD_JUDGE_ID, JudgeLog::FIELD_CREATED_AT]);
            $table->index(JudgeLog::FIELD_TEAM_ID);
            $table->index(JudgeLog::FIELD_SEARCH_EVALUATION_ID);
            $table->index(JudgeLog::FIELD_HTTP_STATUS_CODE);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('judge_logs');
    }
};
