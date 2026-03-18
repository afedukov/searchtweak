<?php

namespace App\Livewire\Forms;

use App\Actions\Fortify\PasswordValidationRules;
use Livewire\Form;

class UserForm extends Form
{
    use PasswordValidationRules;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', $this->passwordConstraints()],
        ];
    }
}
