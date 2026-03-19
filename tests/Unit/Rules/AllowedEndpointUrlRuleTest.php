<?php

namespace Tests\Unit\Rules;

use App\Rules\AllowedEndpointUrlRule;
use Tests\TestCase;

class AllowedEndpointUrlRuleTest extends TestCase
{
    private AllowedEndpointUrlRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new AllowedEndpointUrlRule();
    }

    private function validate(mixed $value): ?string
    {
        $error = null;
        $this->rule->validate('url', $value, function ($message) use (&$error) {
            $error = $message;
        });

        return $error;
    }

    public function test_passes_when_config_is_empty(): void
    {
        config()->set('app.allowed_endpoint_hosts', '');

        $this->assertNull($this->validate('https://any-host.example.com/search'));
    }

    public function test_passes_when_host_is_in_allowlist_single(): void
    {
        config()->set('app.allowed_endpoint_hosts', 'api.example.com');

        $this->assertNull($this->validate('https://api.example.com/v1/search'));
    }

    public function test_passes_when_host_is_in_allowlist_multiple(): void
    {
        config()->set('app.allowed_endpoint_hosts', 'api.example.com,search.example.com');

        $this->assertNull($this->validate('https://search.example.com/query'));
    }

    public function test_fails_when_host_is_not_in_allowlist(): void
    {
        config()->set('app.allowed_endpoint_hosts', 'api.example.com');

        $error = $this->validate('https://evil.example.com/search');

        $this->assertNotNull($error);
        $this->assertStringContainsString('api.example.com', $error);
    }

    public function test_fails_when_url_has_no_valid_host(): void
    {
        config()->set('app.allowed_endpoint_hosts', 'api.example.com');

        $error = $this->validate('not-a-valid-url');

        $this->assertNotNull($error);
    }

    public function test_passes_with_whitespace_around_hosts_in_config(): void
    {
        config()->set('app.allowed_endpoint_hosts', ' api.example.com , search.example.com ');

        $this->assertNull($this->validate('https://search.example.com/query'));
    }

    public function test_fails_for_internal_metadata_url_when_allowlist_is_set(): void
    {
        config()->set('app.allowed_endpoint_hosts', 'api.example.com');

        $error = $this->validate('http://metadata.google.internal/computeMetadata/v1/');

        $this->assertNotNull($error);
        $this->assertStringContainsString('api.example.com', $error);
    }
}
