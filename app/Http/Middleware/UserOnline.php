<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class UserOnline
{
    public const int CACHE_MINUTES = 5;
    private const string CACHE_KEY = 'user-online::%s';

    public static function getCacheKey(int $userId): string
    {
        return sprintf(self::CACHE_KEY, $userId);
    }

    public function handle($request, Closure $next)
    {
        if (Auth::check() && !session()->has('impersonating')) {
            /** @var User $user */
            $user = Auth::user();

            Cache::put(self::getCacheKey($user->id), true, now()->addMinutes(self::CACHE_MINUTES));

            if ($user->last_active_at === null || $user->last_active_at->lessThan(now()->subMinutes(self::CACHE_MINUTES))) {
                $user->forceFill([User::FIELD_LAST_ACTIVE_AT => now()])->saveQuietly();
            }
        }

        return $next($request);
    }
}
