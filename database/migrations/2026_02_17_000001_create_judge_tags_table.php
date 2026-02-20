<?php

use App\Models\JudgeTag;
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
        Schema::create('judge_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId(JudgeTag::FIELD_JUDGE_ID)->constrained('judges')->cascadeOnDelete();
            $table->foreignId(JudgeTag::FIELD_TAG_ID)->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique([JudgeTag::FIELD_JUDGE_ID, JudgeTag::FIELD_TAG_ID]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('judge_tags');
    }
};
