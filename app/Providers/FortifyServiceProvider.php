<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use App\Services\SettingsService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * Override Fortify features from DB before Fortify's boot()
     * registers routes. The 'booting' callback runs after all
     * providers are registered (DB available) but before boot().
     */
    public function register(): void
    {
        $this->overrideFortifyFeatures();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }

    private function overrideFortifyFeatures(): void
    {
        try {
            $settings = DB::table('settings')
                ->pluck(Setting::FIELD_VALUE, Setting::FIELD_KEY);

            if ($settings->isEmpty()) {
                return;
            }

            $enabled = fn (string $key, bool $default) => filter_var(
                $settings->get($key, $default ? 'true' : 'false'),
                FILTER_VALIDATE_BOOLEAN,
            );

            $features = [
                Features::updateProfileInformation(),
                Features::updatePasswords(),
                Features::emailVerification(),
            ];

            if ($enabled(SettingsService::FORTIFY_REGISTRATION, true)) {
                $features[] = Features::registration();
            }

            if ($enabled(SettingsService::FORTIFY_RESET_PASSWORDS, true)) {
                $features[] = Features::resetPasswords();
            }

            if ($enabled(SettingsService::FORTIFY_TWO_FACTOR_AUTHENTICATION, true)) {
                $features[] = Features::twoFactorAuthentication([
                    'confirm' => true,
                    'confirmPassword' => true,
                ]);
            }

            config(['fortify.features' => $features]);
        } catch (\Throwable) {
            // DB not available (migrations, fresh install) — keep config defaults
        }
    }
}
