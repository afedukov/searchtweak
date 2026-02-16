<?php

namespace Tests\Unit\Services;

use App\Services\Helpers;
use Tests\TestCase;

class HelpersTest extends TestCase
{
    public function test_is_url_valid_http(): void
    {
        $this->assertTrue(Helpers::isUrl('http://example.com'));
    }

    public function test_is_url_valid_https(): void
    {
        $this->assertTrue(Helpers::isUrl('https://example.com/path?q=1'));
    }

    public function test_is_url_invalid_string(): void
    {
        $this->assertFalse(Helpers::isUrl('not-a-url'));
    }

    public function test_is_url_empty_string(): void
    {
        $this->assertFalse(Helpers::isUrl(''));
    }

    public function test_get_removed_user_profile_photo_url(): void
    {
        $url = Helpers::getRemovedUserProfilePhotoUrl();

        $this->assertStringContainsString('ui-avatars.com', $url);
        $this->assertStringContainsString('Removed%20User', $url);
    }
}
