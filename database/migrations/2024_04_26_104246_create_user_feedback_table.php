<?php

use App\Models\UserFeedback;
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
        Schema::create('user_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId(UserFeedback::FIELD_SEARCH_SNAPSHOT_ID)->constrained()->cascadeOnDelete();
            $table->foreignId(UserFeedback::FIELD_USER_ID)->nullable()->constrained()->nullOnDelete();
            $table->integer(UserFeedback::FIELD_GRADE)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_feedbacks');
    }
};
