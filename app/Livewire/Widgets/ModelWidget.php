<?php

namespace App\Livewire\Widgets;

use App\Models\SearchModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ModelWidget extends BaseWidget
{
    public SearchModel $model;

    public static function getWidgetName(array $data = null): string
    {
        $model = SearchModel::find($data['id']);
        if ($model === null) {
            return 'Evaluation';
        }

        return sprintf('Model: %s', $model->name);
    }

    public static function isRemovable(): bool
    {
        return true;
    }

    protected function getListeners(): array
    {
        $teamId = Auth::user()->current_team_id;

        return [
            sprintf('echo-private:team.%d,.SearchEvaluationUpdated', $teamId) => '$refresh',
            sprintf('echo-private:team.%d,.SearchEvaluationDeleted', $teamId) => '$refresh',
        ];
    }

    public function mount(): void
    {
        $model = SearchModel::find($this->widget['settings']['id']);
        if ($model === null) {
            $this->skipRender();
            return;
        }

        $this->model = $model;
    }

    public function render(): View
    {
        return view('livewire.widgets.model-widget', [
            'metrics' => $this->model->getMetrics(),
        ]);
    }
}
