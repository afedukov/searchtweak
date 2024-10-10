<?php

use App\Models\UserTag;
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
        Schema::create('user_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId(UserTag::FIELD_USER_ID)->constrained()->cascadeOnDelete();
            $table->foreignId(UserTag::FIELD_TAG_ID)->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique([UserTag::FIELD_USER_ID, UserTag::FIELD_TAG_ID]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_tags');
    }
};
