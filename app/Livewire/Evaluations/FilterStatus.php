<?php

namespace App\Livewire\Evaluations;

use App\Models\SearchEvaluation;
use Illuminate\View\View;
use Livewire\Attributes\Modelable;
use Livewire\Component;

class FilterStatus extends Component
{
    public const array DEFAULT_FILTER_STATUS = [
        SearchEvaluation::STATUS_PENDING,
        SearchEvaluation::STATUS_ACTIVE,
        SearchEvaluation::STATUS_FINISHED,
    ];

    #[Modelable]
    public array $filterStatus = self::DEFAULT_FILTER_STATUS;

    public function render(): View
    {
        return view('livewire.evaluations.filter-status');
    }
}
