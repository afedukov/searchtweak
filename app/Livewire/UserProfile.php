<?php

namespace App\Livewire;

use Illuminate\View\View;
use Livewire\Component;

class UserProfile extends Component
{
    public function render(): View
    {
        return view('profile.show')
            ->title('User Profile');
    }
}
