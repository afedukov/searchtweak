<?php

use App\Models\NotificationUnsubscription;
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
        Schema::create('notification_unsubscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId(NotificationUnsubscription::FIELD_USER_ID)->constrained()->cascadeOnDelete();
            $table->string(NotificationUnsubscription::FIELD_NOTIFICATION_CLASS);
            $table->timestamps();

            $table->unique([NotificationUnsubscription::FIELD_USER_ID, NotificationUnsubscription::FIELD_NOTIFICATION_CLASS]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_unsubscriptions');
    }
};
