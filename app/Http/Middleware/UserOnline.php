<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class UserOnline
{
    private const int CACHE_MINUTES = 5;
    private const string CACHE_KEY = 'user-online::%s';

    public static function getCacheKey(int $userId): string
    {
        return sprintf(self::CACHE_KEY, $userId);
    }

    public function handle($request, Closure $next)
    {
        if (Auth::check() && !session()->has('impersonating')) {
            Cache::put(self::getCacheKey(Auth::user()->id), true, now()->addMinutes(self::CACHE_MINUTES));
        }

        return $next($request);
    }
}
