<?php

namespace App\Livewire\Evaluations;

use Illuminate\View\View;
use Livewire\Attributes\Modelable;
use Livewire\Component;

class FilterArchived extends Component
{
    #[Modelable]
    public string $filter;

    public function render(): View
    {
        return view('livewire.evaluations.filter-archived');
    }
}
