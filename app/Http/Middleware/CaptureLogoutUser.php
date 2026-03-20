<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CaptureLogoutUser
{
    /**
     * Capture the authenticated user before Fortify logs them out,
     * so OidcLogoutResponse can check if they were an OIDC user.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('logout') && Auth::check()) {
            $request->attributes->set('logout_user', Auth::user());
        }

        return $next($request);
    }
}
