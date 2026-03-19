<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AllowedEndpointUrlRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $allowedHosts = config('app.allowed_endpoint_hosts');

        if (empty($allowedHosts)) {
            return;
        }

        $hosts = array_map('trim', explode(',', $allowedHosts));

        $host = parse_url($value, PHP_URL_HOST);

        if (empty($host) || !in_array($host, $hosts, true)) {
            $fail('The endpoint URL must point to one of the allowed hosts: ' . implode(', ', $hosts));
        }
    }
}
