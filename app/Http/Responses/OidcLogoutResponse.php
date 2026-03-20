<?php

namespace App\Http\Responses;

use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\Response;

class OidcLogoutResponse implements LogoutResponseContract
{
    public function toResponse($request): Response
    {
        $user = $request->attributes->get('logout_user');

        if ($user instanceof User && $user->oidc_id && $this->isSsoEnabled()) {
            $logoutUrl = Socialite::driver('keycloak')->getLogoutUrl(
                redirectUri: url('/login'),
                clientId: config('services.keycloak.client_id'),
            );

            return $request->wantsJson()
                ? new JsonResponse(['redirect' => $logoutUrl], 200)
                : redirect($logoutUrl);
        }

        return $request->wantsJson()
            ? new JsonResponse('', 204)
            : redirect('/login');
    }

    private function isSsoEnabled(): bool
    {
        $settings = app(SettingsService::class);

        return $settings->getBoolean(SettingsService::SSO_ENABLED)
            && !empty(config('services.keycloak.client_id'))
            && !empty(config('services.keycloak.base_url'));
    }
}
