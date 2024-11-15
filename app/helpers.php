<?php

/**
 * Generate a unique string identifier.
 *
 * This function generates a unique key by combining the current time in
 * microseconds with additional entropy. It is suitable for creating unique
 * identifiers in single-threaded environments, such as keys for Livewire
 * components, cache entries, or other temporary purposes.
 *
 * Note: This function is not cryptographically secure and should not be
 * used for generating sensitive data such as passwords, API tokens, or
 * other security-critical identifiers.
 *
 * @return string A string representing a unique identifier with added entropy.
 */
if (!function_exists('unique_key')) {
    function unique_key(): string
    {
        return uniqid('', true);
    }
}
