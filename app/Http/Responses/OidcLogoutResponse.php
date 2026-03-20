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
    public function __construct(private readonly SettingsService $settings)
    {
    }

    public function toResponse($request): Response
    {
        $user = $request->attributes->get('logout_user');

        if ($user instanceof User && $user->oidc_id && $this->settings->isSsoEnabled()) {
            $logoutUrl = Socialite::driver('keycloak')->getLogoutUrl(
                redirectUri: route('login'),
                clientId: config('services.keycloak.client_id'),
            );

            return $request->wantsJson()
                ? new JsonResponse(['redirect' => $logoutUrl], 200)
                : redirect($logoutUrl);
        }

        return $request->wantsJson()
            ? new JsonResponse('', 204)
            : redirect(route('login'));
    }
}
