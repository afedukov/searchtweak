<?php

namespace App\Services;

use Illuminate\Support\Str;

class Helpers
{
    /**
     * Determine if the given string is a valid URL.
     *
     * @param string $url
     *
     * @return bool
     */
    public static function isUrl(string $url): bool
    {
        return Str::isUrl($url);
    }

    public static function getRemovedUserProfilePhotoUrl(): string
    {
        return 'https://ui-avatars.com/api/?name=Removed%20User&color=7F9CF5&background=EBF4FF';
    }
}
