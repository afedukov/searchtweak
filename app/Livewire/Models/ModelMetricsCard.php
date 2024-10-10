<?php

namespace App\Livewire\Models;

use App\Livewire\Traits\ControlWidgetTrait;
use App\Livewire\Widgets\ModelWidget;
use App\Models\SearchModel;
use App\Models\UserWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class ModelMetricsCard extends Component
{
    use ControlWidgetTrait;

    public SearchModel $model;

    protected function getWidgetClass(): string
    {
        return ModelWidget::class;
    }

    protected function getWidgetEntityId(): int
    {
        return $this->model->id;
    }

    public function render(): View
    {
        $attached = Auth::user()->widgets()
            ->where(UserWidget::FIELD_WIDGET_CLASS, ModelWidget::class)
            ->where(UserWidget::FIELD_SETTINGS . '->id', $this->model->id)
            ->exists();

        return view('livewire.models.model-metrics-card', [
            'metrics' => $this->model->getMetrics(),
            'attached' => $attached,
        ]);
    }
}
