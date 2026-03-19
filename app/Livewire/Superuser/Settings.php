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

    private const array PROPERTY_TO_SETTING = [
        'registration' => SettingsService::FORTIFY_REGISTRATION,
        'resetPasswords' => SettingsService::FORTIFY_RESET_PASSWORDS,
        'emailVerification' => SettingsService::FORTIFY_EMAIL_VERIFICATION,
        'twoFactorAuthentication' => SettingsService::FORTIFY_TWO_FACTOR_AUTHENTICATION,
    ];

    private const array PROPERTY_LABELS = [
        'registration' => 'Registration',
        'resetPasswords' => 'Password reset',
        'emailVerification' => 'Email verification',
        'twoFactorAuthentication' => 'Two-factor authentication',
    ];

    public function mount(SettingsService $settings): void
    {
        $this->registration = $settings->getBoolean(SettingsService::FORTIFY_REGISTRATION, true);
        $this->resetPasswords = $settings->getBoolean(SettingsService::FORTIFY_RESET_PASSWORDS, true);
        $this->emailVerification = $settings->getBoolean(SettingsService::FORTIFY_EMAIL_VERIFICATION, false);
        $this->twoFactorAuthentication = $settings->getBoolean(SettingsService::FORTIFY_TWO_FACTOR_AUTHENTICATION, true);
    }

    public function updated(string $property): void
    {
        if (!isset(self::PROPERTY_TO_SETTING[$property])) {
            return;
        }

        Gate::authorize('superuser', Auth::user());

        $settings = app(SettingsService::class);
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
