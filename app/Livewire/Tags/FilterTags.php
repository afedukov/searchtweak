<?php

namespace App\Livewire\Tags;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Modelable;
use Livewire\Component;

class FilterTags extends Component
{
    #[Modelable]
    public int $tag = 0;

    public Collection $tags;

    public function render(): View
    {
        return view('livewire.tags.filter-tags');
    }
}
