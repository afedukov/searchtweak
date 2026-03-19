<?php

namespace App\Http\Middleware;

use App\Services\SettingsService;
use Closure;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerifiedIfEnabled
{
    public function handle(Request $request, Closure $next, ?string $redirectToRoute = null): Response
    {
        $settings = app(SettingsService::class);

        if ($settings->getBoolean(SettingsService::FORTIFY_EMAIL_VERIFICATION)) {
            return app(EnsureEmailIsVerified::class)->handle($request, $next, $redirectToRoute);
        }

        return $next($request);
    }
}
