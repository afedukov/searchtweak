<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
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
        if (!$settings->isSsoEnabled()) {
            abort(404);
        }

        return Socialite::driver('keycloak')->redirect();
    }

    public function callback(SettingsService $settings): RedirectResponse
    {
        if (!$settings->isSsoEnabled()) {
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
            $user = User::forceCreate([
                User::FIELD_NAME => $oidcUser->getName() ?? $oidcUser->getEmail(),
                User::FIELD_EMAIL => $oidcUser->getEmail(),
                User::FIELD_PASSWORD => Hash::make(Str::random(32)),
                User::FIELD_OIDC_ID => $oidcUser->getId(),
                User::FIELD_EMAIL_VERIFIED_AT => now(),
            ]);

            $user->createPersonalTeam();

            return $user;
        });

        Auth::login($user, remember: true);

        return redirect()->route('dashboard');
    }
}
