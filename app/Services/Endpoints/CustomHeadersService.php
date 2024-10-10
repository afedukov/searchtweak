<?php

namespace App\Services\Endpoints;

class CustomHeadersService
{
    public function composeHeadersArray(string $source): array
    {
        $lines = array_unique(array_filter(explode("\n", $source)));

        $headers = [];

        foreach ($lines as $line) {
            $parts = explode(':', $line);
            if (count($parts) !== 2) {
                continue;
            }

            $key = trim($parts[0]);
            $value = trim($parts[1]);

            if (empty($key) || empty($value)) {
                continue;
            }

            $headers[$key] = $value;
        }

        return $headers;
    }

    public function decomposeHeadersArray(array $headers): string
    {
        $lines = [];

        foreach ($headers as $key => $value) {
            $lines[] = "$key: $value";
        }

        return implode("\n", $lines);
    }
}
