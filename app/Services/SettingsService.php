<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    public const string FORTIFY_REGISTRATION = 'fortify.registration';
    public const string FORTIFY_RESET_PASSWORDS = 'fortify.reset_passwords';
    public const string FORTIFY_EMAIL_VERIFICATION = 'fortify.email_verification';
    public const string FORTIFY_TWO_FACTOR_AUTHENTICATION = 'fortify.two_factor_authentication';

    public const string SSO_ENABLED = 'sso.enabled';
    public const string SSO_ONLY_MODE = 'sso.only_mode';

    private const int CACHE_TTL_SECONDS = 3600;

    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember(
            $this->cacheKey($key),
            self::CACHE_TTL_SECONDS,
            fn () => Setting::query()
                ->where(Setting::FIELD_KEY, $key)
                ->value(Setting::FIELD_VALUE) ?? $default
        );
    }

    public function set(string $key, mixed $value): void
    {
        Setting::query()
            ->updateOrCreate(
                [Setting::FIELD_KEY => $key],
                [Setting::FIELD_VALUE => (string) $value],
            );

        Cache::forget($this->cacheKey($key));
    }

    public function getBoolean(string $key, bool $default = false): bool
    {
        $value = $this->get($key);

        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Check if OIDC provider is configured.
     */
    public function isSsoConfigured(): bool
    {
        return !empty(config('services.keycloak.client_id')) && !empty(config('services.keycloak.base_url'));
    }

    /**
     * Check if SSO is enabled in settings and OIDC provider is configured.
     */
    public function isSsoEnabled(): bool
    {
        return $this->getBoolean(self::SSO_ENABLED) && $this->isSsoConfigured();
    }

    /**
     * Check if SSO Only Mode is active (SSO enabled + only mode setting on).
     */
    public function isSsoOnlyMode(): bool
    {
        return $this->isSsoEnabled() && $this->getBoolean(self::SSO_ONLY_MODE);
    }

    private function cacheKey(string $key): string
    {
        return "settings:{$key}";
    }
}
