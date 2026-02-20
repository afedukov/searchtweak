<?php

namespace App\Services\Judges;

class JudgeParamsService
{
    /**
     * Parse a textarea string of "key: value" lines into a typed array.
     * Numeric values are cast to int or float, booleans to bool, "null" to null.
     */
    public function composeParamsArray(string $source): array
    {
        $lines = array_unique(array_filter(explode("\n", $source)));

        $params = [];

        foreach ($lines as $line) {
            $colonPos = strpos($line, ':');

            if ($colonPos === false) {
                continue;
            }

            $key = trim(substr($line, 0, $colonPos));
            $value = trim(substr($line, $colonPos + 1));

            if (empty($key)) {
                continue;
            }

            $params[$key] = $this->castValue($value);
        }

        return $params;
    }

    /**
     * Convert a params array back to a "key: value" textarea string.
     */
    public function decomposeParamsArray(array $params): string
    {
        $lines = [];

        foreach ($params as $key => $value) {
            if (is_bool($value)) {
                $lines[] = "$key: " . ($value ? 'true' : 'false');
            } elseif (is_null($value)) {
                $lines[] = "$key: null";
            } else {
                $lines[] = "$key: $value";
            }
        }

        return implode("\n", $lines);
    }

    private function castValue(string $value): mixed
    {
        if ($value === 'true') {
            return true;
        }

        if ($value === 'false') {
            return false;
        }

        if ($value === 'null') {
            return null;
        }

        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        return $value;
    }
}
