<?php

namespace App\Livewire;

use App\Livewire\Evaluations\FilterStatus;
use App\Livewire\Traits\Evaluations\DeleteEvaluationTrait;
use App\Livewire\Traits\Evaluations\EditEvaluationModalTrait;
use App\Livewire\Traits\Evaluations\ExportEvaluationTrait;
use App\Livewire\Traits\Models\EditModelModalTrait;
use App\Models\SearchEvaluation;
use App\Models\SearchModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Model extends Component
{
    use WithPagination;
    use DeleteEvaluationTrait;
    use EditEvaluationModalTrait;
    use EditModelModalTrait;
    use ExportEvaluationTrait;

    public const int PER_PAGE = 10;

    public SearchModel $model;

    public array $filterStatus = FilterStatus::DEFAULT_FILTER_STATUS;

    public int $filterTagId = 0;

    protected function getListeners(): array
    {
        $teamId = Auth::user()->current_team_id;

        return [
            sprintf('echo-private:team.%d,.SearchEvaluationCreated', $teamId) => '$refresh',
            sprintf('echo-private:team.%d,.SearchEvaluationUpdated', $teamId) => '$refresh',
            sprintf('echo-private:team.%d,.SearchEvaluationDeleted', $teamId) => '$refresh',
        ];
    }

    public function mount(SearchModel $model): void
    {
        $this->model = $model->load('user', 'team', 'endpoint', 'tags');

        $this->initializeEditModel();
    }

    public function render(): View
    {
        $allModels = Auth::user()->currentTeam
            ->models()
            ->with('tags')
            ->get();

        return view('livewire.pages.model', [
            'evaluations' => $this->model
                ->evaluations()
                ->whereIn(SearchEvaluation::FIELD_STATUS, $this->filterStatus)
                ->when($this->filterTagId, fn (Builder $query) =>
                    $query->whereHas('tags', fn (Builder $query) => $query->whereKey($this->filterTagId))
                )
                ->with('user', 'model.team', 'metrics', 'tags')
                ->withCount('keywords')
                ->orderByDesc(SearchEvaluation::FIELD_ID)
                ->paginate(self::PER_PAGE),
            'allModels' => $allModels,
        ])->title($this->model->name);
    }
}
