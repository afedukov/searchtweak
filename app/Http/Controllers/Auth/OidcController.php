<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class OidcController extends Controller
{
    public function redirect(SettingsService $settings): RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        if (!$this->isSsoAvailable($settings)) {
            abort(404);
        }

        return Socialite::driver('keycloak')->redirect();
    }

    public function callback(SettingsService $settings): RedirectResponse
    {
        if (!$this->isSsoAvailable($settings)) {
            abort(404);
        }

        $oidcUser = Socialite::driver('keycloak')->user();

        $user = DB::transaction(function () use ($oidcUser) {
            $user = User::where(User::FIELD_EMAIL, $oidcUser->getEmail())->first();

            if ($user) {
                if (!$user->oidc_id) {
                    $user->update([User::FIELD_OIDC_ID => $oidcUser->getId()]);
                }

                return $user;
            }

            // Create new user from OIDC data
            $user = User::create([
                User::FIELD_NAME => $oidcUser->getName() ?? $oidcUser->getEmail(),
                User::FIELD_EMAIL => $oidcUser->getEmail(),
                User::FIELD_PASSWORD => Hash::make(Str::random(32)),
                User::FIELD_OIDC_ID => $oidcUser->getId(),
            ]);

            $user->forceFill([
                User::FIELD_EMAIL_VERIFIED_AT => now(),
            ])->save();

            // Create personal team
            $user->ownedTeams()->save(Team::forceCreate([
                'user_id' => $user->id,
                'name' => explode(' ', $user->name, 2)[0] . "'s Team",
                'personal_team' => true,
            ]));

            return $user;
        });

        Auth::login($user, remember: true);

        return redirect()->route('dashboard');
    }

    private function isSsoAvailable(SettingsService $settings): bool
    {
        return $settings->getBoolean(SettingsService::SSO_ENABLED)
            && !empty(config('services.keycloak.client_id'))
            && !empty(config('services.keycloak.base_url'));
    }
}
