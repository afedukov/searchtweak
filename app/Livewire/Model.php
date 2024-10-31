<?php

namespace App\Livewire;

use App\Livewire\Traits\Evaluations\ArchiveEvaluationTrait;
use App\Livewire\Traits\Evaluations\BaselineEvaluationTrait;
use App\Livewire\Traits\Evaluations\DeleteEvaluationTrait;
use App\Livewire\Traits\Evaluations\EditEvaluationModalTrait;
use App\Livewire\Traits\Evaluations\ExportEvaluationTrait;
use App\Livewire\Traits\Evaluations\FilterEvaluationsTrait;
use App\Livewire\Traits\Evaluations\PinEvaluationTrait;
use App\Livewire\Traits\Models\EditModelModalTrait;
use App\Models\SearchEvaluation;
use App\Models\SearchModel;
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
    use FilterEvaluationsTrait;
    use ArchiveEvaluationTrait;
    use PinEvaluationTrait;
    use BaselineEvaluationTrait;

    public const int PER_PAGE = 10;

    public SearchModel $model;

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

        $query = SearchEvaluation::query()
            ->where(SearchEvaluation::FIELD_MODEL_ID, $this->model->id);

        return view('livewire.pages.model', [
            'evaluations' => $this->applyFilters($query)
                ->with('user', 'model.team', 'metrics', 'tags')
                ->withCount('keywords')
                ->orderByDesc(SearchEvaluation::FIELD_PINNED)
                ->orderByDesc(SearchEvaluation::FIELD_ID)
                ->paginate(self::PER_PAGE),
            'allModels' => $allModels,
        ])->title($this->model->name);
    }
}
