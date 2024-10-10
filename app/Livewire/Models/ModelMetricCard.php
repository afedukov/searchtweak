<?php

namespace App\Livewire\Models;

use App\Services\Models\ModelMetric;
use Illuminate\View\View;
use Livewire\Component;

class ModelMetricCard extends Component
{
    public ModelMetric $metric;

    public function render(): View
    {
        return view('livewire.models.model-metric-card');
    }
}
