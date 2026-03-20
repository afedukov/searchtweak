<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class OidcAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure settings exist
        Setting::query()->updateOrCreate(
            [Setting::FIELD_KEY => SettingsService::SSO_ENABLED],
            [Setting::FIELD_VALUE => 'false'],
        );
        Setting::query()->updateOrCreate(
            [Setting::FIELD_KEY => SettingsService::SSO_ONLY_MODE],
            [Setting::FIELD_VALUE => 'false'],
        );
    }

    public function test_sso_redirect_returns_404_when_disabled(): void
    {
        $response = $this->get('/auth/oidc/redirect');

        $response->assertStatus(404);
    }

    public function test_sso_redirect_returns_404_when_env_not_configured(): void
    {
        $this->enableSso();

        config(['services.keycloak.client_id' => null]);
        config(['services.keycloak.base_url' => null]);

        // Clear settings cache
        app(SettingsService::class)->set(SettingsService::SSO_ENABLED, 'true');

        $response = $this->get('/auth/oidc/redirect');

        $response->assertStatus(404);
    }

    public function test_sso_redirect_works_when_enabled_and_configured(): void
    {
        $this->enableSso();
        $this->configureOidc();

        $provider = Mockery::mock(\Laravel\Socialite\Two\AbstractProvider::class);
        $provider->shouldReceive('redirect')
            ->once()
            ->andReturn(redirect('https://idp.example.com/auth'));

        Socialite::shouldReceive('driver')
            ->with('keycloak')
            ->once()
            ->andReturn($provider);

        $response = $this->get('/auth/oidc/redirect');

        $response->assertRedirect('https://idp.example.com/auth');
    }

    public function test_callback_creates_new_user_with_personal_team(): void
    {
        $this->enableSso();
        $this->configureOidc();
        $this->mockSocialiteUser('oidc-123', 'Jane Doe', 'jane@example.com');

        $response = $this->get('/auth/oidc/callback');

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $user = User::where(User::FIELD_EMAIL, 'jane@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('oidc-123', $user->oidc_id);
        $this->assertEquals('Jane Doe', $user->name);
        $this->assertNotNull($user->email_verified_at);

        // Check personal team was created
        $this->assertCount(1, $user->ownedTeams);
        $this->assertTrue($user->ownedTeams->first()->personal_team);
        $this->assertEquals("Jane's Team", $user->ownedTeams->first()->name);
    }

    public function test_callback_logs_in_existing_user_matched_by_email(): void
    {
        $this->enableSso();
        $this->configureOidc();

        $user = User::factory()->create([
            'email' => 'existing@example.com',
            'name' => 'Existing User',
        ]);

        $this->mockSocialiteUser('oidc-456', 'Existing User', 'existing@example.com');

        $response = $this->get('/auth/oidc/callback');

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_callback_updates_oidc_id_on_existing_user(): void
    {
        $this->enableSso();
        $this->configureOidc();

        $user = User::factory()->create([
            'email' => 'link@example.com',
            'oidc_id' => null,
        ]);

        $this->mockSocialiteUser('oidc-789', $user->name, 'link@example.com');

        $this->get('/auth/oidc/callback');

        $user->refresh();
        $this->assertEquals('oidc-789', $user->oidc_id);
    }

    public function test_callback_does_not_overwrite_existing_oidc_id(): void
    {
        $this->enableSso();
        $this->configureOidc();

        $user = User::factory()->create([
            'email' => 'linked@example.com',
            'oidc_id' => 'original-id',
        ]);

        $this->mockSocialiteUser('new-id', $user->name, 'linked@example.com');

        $this->get('/auth/oidc/callback');

        $user->refresh();
        $this->assertEquals('original-id', $user->oidc_id);
    }

    public function test_login_page_shows_sso_button_when_enabled(): void
    {
        $this->enableSso();
        $this->configureOidc();

        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee(route('oidc.redirect'));
        $response->assertSee('Sign in with SSO');
    }

    public function test_login_page_hides_form_in_sso_only_mode(): void
    {
        $this->enableSso();
        $this->enableSsoOnlyMode();
        $this->configureOidc();

        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee(route('oidc.redirect'));
        $response->assertSee('Use your corporate account to sign in.');
        $response->assertDontSee('name="password"');
    }

    public function test_fallback_param_shows_form_in_sso_only_mode(): void
    {
        $this->enableSso();
        $this->enableSsoOnlyMode();
        $this->configureOidc();

        $response = $this->get('/login?fallback=1');

        $response->assertStatus(200);
        $response->assertSee(route('oidc.redirect'));
        $response->assertSee('name="password"');
    }

    public function test_login_page_unchanged_when_sso_disabled(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertDontSee(route('oidc.redirect'));
        $response->assertSee('name="password"');
    }

    // --- Helpers ---

    private function enableSso(): void
    {
        app(SettingsService::class)->set(SettingsService::SSO_ENABLED, 'true');
    }

    private function enableSsoOnlyMode(): void
    {
        app(SettingsService::class)->set(SettingsService::SSO_ONLY_MODE, 'true');
    }

    private function configureOidc(): void
    {
        config([
            'services.keycloak.client_id' => 'test-client-id',
            'services.keycloak.client_secret' => 'test-secret',
            'services.keycloak.base_url' => 'https://idp.example.com/realms/test',
        ]);
    }

    private function mockSocialiteUser(string $id, string $name, string $email): void
    {
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getId')->andReturn($id);
        $socialiteUser->shouldReceive('getName')->andReturn($name);
        $socialiteUser->shouldReceive('getEmail')->andReturn($email);

        $provider = Mockery::mock(\Laravel\Socialite\Two\AbstractProvider::class);
        $provider->shouldReceive('user')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')
            ->with('keycloak')
            ->andReturn($provider);
    }
}
