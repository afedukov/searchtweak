<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Superuser\Settings;
use App\Models\Setting;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SuperuserSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_settings_page(): void
    {
        $admin = User::factory()->withPersonalTeam()->create([
            User::FIELD_SUPER_ADMIN => true,
        ]);

        $this->actingAs($admin)
            ->get(route('superuser.settings'))
            ->assertOk();
    }

    public function test_non_super_admin_cannot_view_settings_page(): void
    {
        $user = User::factory()->withPersonalTeam()->create([
            User::FIELD_SUPER_ADMIN => false,
        ]);

        $this->actingAs($user)
            ->get(route('superuser.settings'))
            ->assertForbidden();
    }

    public function test_super_admin_can_toggle_registration(): void
    {
        $admin = User::factory()->withPersonalTeam()->create([
            User::FIELD_SUPER_ADMIN => true,
        ]);

        // Migration seeds 'true' by default
        Livewire::actingAs($admin)
            ->test(Settings::class)
            ->assertSet('registration', true)
            ->set('registration', false)
            ->assertSet('registration', false);

        $this->assertEquals('false', Setting::where('key', SettingsService::FORTIFY_REGISTRATION)->value('value'));
    }

    public function test_super_admin_can_toggle_reset_passwords(): void
    {
        $admin = User::factory()->withPersonalTeam()->create([
            User::FIELD_SUPER_ADMIN => true,
        ]);

        Livewire::actingAs($admin)
            ->test(Settings::class)
            ->assertSet('resetPasswords', true)
            ->set('resetPasswords', false)
            ->assertSet('resetPasswords', false);

        $this->assertEquals('false', Setting::where('key', SettingsService::FORTIFY_RESET_PASSWORDS)->value('value'));
    }

    public function test_super_admin_can_toggle_email_verification(): void
    {
        $admin = User::factory()->withPersonalTeam()->create([
            User::FIELD_SUPER_ADMIN => true,
        ]);

        // Migration seeds 'false' by default
        Livewire::actingAs($admin)
            ->test(Settings::class)
            ->assertSet('emailVerification', false)
            ->set('emailVerification', true)
            ->assertSet('emailVerification', true);

        $this->assertEquals('true', Setting::where('key', SettingsService::FORTIFY_EMAIL_VERIFICATION)->value('value'));
    }

    public function test_super_admin_can_toggle_two_factor_authentication(): void
    {
        $admin = User::factory()->withPersonalTeam()->create([
            User::FIELD_SUPER_ADMIN => true,
        ]);

        Livewire::actingAs($admin)
            ->test(Settings::class)
            ->assertSet('twoFactorAuthentication', true)
            ->set('twoFactorAuthentication', false)
            ->assertSet('twoFactorAuthentication', false);

        $this->assertEquals('false', Setting::where('key', SettingsService::FORTIFY_TWO_FACTOR_AUTHENTICATION)->value('value'));
    }

    public function test_non_super_admin_cannot_toggle_settings(): void
    {
        $user = User::factory()->withPersonalTeam()->create([
            User::FIELD_SUPER_ADMIN => false,
        ]);

        Livewire::actingAs($user)
            ->test(Settings::class)
            ->set('registration', false);

        // Should remain unchanged
        $this->assertEquals('true', Setting::where('key', SettingsService::FORTIFY_REGISTRATION)->value('value'));
    }

    public function test_settings_service_reads_from_database(): void
    {
        // Migration seeds 'true', update to 'false'
        Setting::where('key', SettingsService::FORTIFY_REGISTRATION)->update(['value' => 'false']);

        $service = app(SettingsService::class);

        $this->assertFalse($service->getBoolean(SettingsService::FORTIFY_REGISTRATION, true));
    }

    public function test_settings_service_returns_default_when_key_missing(): void
    {
        // Delete the seeded rows to test defaults
        Setting::query()->delete();

        $service = app(SettingsService::class);

        $this->assertTrue($service->getBoolean(SettingsService::FORTIFY_REGISTRATION, true));
        $this->assertFalse($service->getBoolean(SettingsService::FORTIFY_EMAIL_VERIFICATION, false));
    }
}
