<?php

namespace App\Actions\Fortify;

use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    protected function passwordConstraints(): Password
    {
        return (new Password(8))->mixedCase()->letters()->numbers();
    }

    public function passwordHint(): string
    {
        return 'Minimum 8 characters, must include uppercase and lowercase letters and numbers.';
    }

    /**
     * Get the validation rules used to validate passwords.
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    protected function passwordRules(): array
    {
        return ['required', 'string', $this->passwordConstraints(), 'confirmed'];
    }
}
