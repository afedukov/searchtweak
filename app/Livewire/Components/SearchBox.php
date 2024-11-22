<?php

namespace App\Livewire\Components;

use Illuminate\View\View;
use Livewire\Attributes\Modelable;
use Livewire\Component;

class SearchBox extends Component
{
    #[Modelable]
    public string $query = '';

    public string $placeholder = 'Search...';

    public function render(): View
    {
        return view('livewire.components.search-box');
    }
}
