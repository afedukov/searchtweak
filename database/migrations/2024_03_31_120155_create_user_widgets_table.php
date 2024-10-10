<?php

use App\Models\UserWidget;
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
        Schema::create('user_widgets', function (Blueprint $table) {
            $table->uuid(UserWidget::FIELD_ID)->primary();
            $table->foreignId(UserWidget::FIELD_USER_ID)->constrained()->cascadeOnDelete();
            $table->foreignId(UserWidget::FIELD_TEAM_ID)->constrained()->cascadeOnDelete();
            $table->string(UserWidget::FIELD_WIDGET_CLASS);
            $table->integer(UserWidget::FIELD_POSITION);
            $table->boolean(UserWidget::FIELD_VISIBLE)->default(true);
            $table->json(UserWidget::FIELD_SETTINGS)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_widgets');
    }
};
