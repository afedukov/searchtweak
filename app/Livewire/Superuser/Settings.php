<?php

namespace App\Livewire\Superuser;

use App\Services\SettingsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;
use Toaster;

class Settings extends Component
{
    public bool $registration = true;
    public bool $resetPasswords = true;
    public bool $emailVerification = false;
    public bool $twoFactorAuthentication = true;
    public bool $ssoEnabled = false;
    public bool $ssoOnlyMode = false;
    public bool $ssoConfigured = false;

    private const array PROPERTY_TO_SETTING = [
        'registration' => SettingsService::FORTIFY_REGISTRATION,
        'resetPasswords' => SettingsService::FORTIFY_RESET_PASSWORDS,
        'emailVerification' => SettingsService::FORTIFY_EMAIL_VERIFICATION,
        'twoFactorAuthentication' => SettingsService::FORTIFY_TWO_FACTOR_AUTHENTICATION,
        'ssoEnabled' => SettingsService::SSO_ENABLED,
        'ssoOnlyMode' => SettingsService::SSO_ONLY_MODE,
    ];

    private const array PROPERTY_LABELS = [
        'registration' => 'Registration',
        'resetPasswords' => 'Password reset',
        'emailVerification' => 'Email verification',
        'twoFactorAuthentication' => 'Two-factor authentication',
        'ssoEnabled' => 'SSO (OpenID Connect)',
        'ssoOnlyMode' => 'SSO Only Mode',
    ];

    public function mount(SettingsService $settings): void
    {
        $this->registration = $settings->getBoolean(SettingsService::FORTIFY_REGISTRATION, true);
        $this->resetPasswords = $settings->getBoolean(SettingsService::FORTIFY_RESET_PASSWORDS, true);
        $this->emailVerification = $settings->getBoolean(SettingsService::FORTIFY_EMAIL_VERIFICATION, false);
        $this->twoFactorAuthentication = $settings->getBoolean(SettingsService::FORTIFY_TWO_FACTOR_AUTHENTICATION, true);
        $this->ssoEnabled = $settings->getBoolean(SettingsService::SSO_ENABLED, false);
        $this->ssoOnlyMode = $settings->getBoolean(SettingsService::SSO_ONLY_MODE, false);
        $this->ssoConfigured = $settings->isSsoConfigured();
    }

    public function updated(string $property): void
    {
        Gate::authorize('superuser', Auth::user());

        if (!isset(self::PROPERTY_TO_SETTING[$property])) {
            return;
        }

        // Prevent enabling SSO Only Mode when SSO is disabled
        if ($property === 'ssoOnlyMode' && !$this->ssoEnabled) {
            $this->ssoOnlyMode = false;
            return;
        }

        $settings = app(SettingsService::class);

        // Disable SSO Only Mode when SSO is turned off
        if ($property === 'ssoEnabled' && !$this->ssoEnabled && $this->ssoOnlyMode) {
            $this->ssoOnlyMode = false;
            $settings->set(SettingsService::SSO_ONLY_MODE, 'false');
        }

        $settings->set(self::PROPERTY_TO_SETTING[$property], $this->{$property} ? 'true' : 'false');

        $label = self::PROPERTY_LABELS[$property];
        Toaster::info($this->{$property} ? "{$label} enabled." : "{$label} disabled.");
    }

    public function render(): View
    {
        return view('livewire.superuser.settings')
            ->title('Admin: Settings');
    }
}
