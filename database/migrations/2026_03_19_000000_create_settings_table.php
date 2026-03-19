<?php

use App\Models\Setting;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string(Setting::FIELD_KEY)->unique();
            $table->string(Setting::FIELD_VALUE);
            $table->timestamps();
        });

        Setting::query()
            ->insert([
                [Setting::FIELD_KEY => SettingsService::FORTIFY_REGISTRATION, Setting::FIELD_VALUE => 'true', Setting::FIELD_CREATED_AT => now(), Setting::FIELD_UPDATED_AT => now()],
                [Setting::FIELD_KEY => SettingsService::FORTIFY_RESET_PASSWORDS, Setting::FIELD_VALUE => 'true', Setting::FIELD_CREATED_AT => now(), Setting::FIELD_UPDATED_AT => now()],
                [Setting::FIELD_KEY => SettingsService::FORTIFY_EMAIL_VERIFICATION, Setting::FIELD_VALUE => 'false', Setting::FIELD_CREATED_AT => now(), Setting::FIELD_UPDATED_AT => now()],
                [Setting::FIELD_KEY => SettingsService::FORTIFY_TWO_FACTOR_AUTHENTICATION, Setting::FIELD_VALUE => 'true', Setting::FIELD_CREATED_AT => now(), Setting::FIELD_UPDATED_AT => now()],
            ]);

        // Verify super admin's email
        User::query()
            ->where(User::FIELD_SUPER_ADMIN, true)
            ->whereNull(User::FIELD_EMAIL_VERIFIED_AT)
            ->update([User::FIELD_EMAIL_VERIFIED_AT => now()]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
