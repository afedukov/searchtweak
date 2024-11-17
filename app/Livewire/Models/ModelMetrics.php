<?php

namespace App\Livewire\Models;

use App\Models\SearchModel;
use Illuminate\View\View;
use Livewire\Component;

class ModelMetrics extends Component
{
    public SearchModel $model;

    protected function getListeners(): array
    {
        return [
            sprintf('echo-private:team.%d,.evaluation.metric.changed', $this->model->team_id) => '$refresh',
        ];
    }

    public function render(): View
    {
        return view('livewire.models.model-metrics');
    }
}
