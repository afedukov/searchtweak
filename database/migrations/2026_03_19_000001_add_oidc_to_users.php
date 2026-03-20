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
        Schema::table('users', function (Blueprint $table) {
            $table->string(User::FIELD_OIDC_ID)->nullable()->after(User::FIELD_REMEMBER_TOKEN);
        });

        Setting::query()
            ->insert([
                [Setting::FIELD_KEY => SettingsService::SSO_ENABLED, Setting::FIELD_VALUE => 'false', Setting::FIELD_CREATED_AT => now(), Setting::FIELD_UPDATED_AT => now()],
                [Setting::FIELD_KEY => SettingsService::SSO_ONLY_MODE, Setting::FIELD_VALUE => 'false', Setting::FIELD_CREATED_AT => now(), Setting::FIELD_UPDATED_AT => now()],
            ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(User::FIELD_OIDC_ID);
        });

        Setting::query()
            ->whereIn(Setting::FIELD_KEY, [SettingsService::SSO_ENABLED, SettingsService::SSO_ONLY_MODE])
            ->delete();
    }
};
