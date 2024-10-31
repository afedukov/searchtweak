<?php

namespace App\Livewire;

use App\Livewire\Traits\Evaluations\ArchiveEvaluationTrait;
use App\Livewire\Traits\Evaluations\BaselineEvaluationTrait;
use App\Livewire\Traits\Evaluations\DeleteEvaluationTrait;
use App\Livewire\Traits\Evaluations\EditEvaluationModalTrait;
use App\Livewire\Traits\Evaluations\ExportEvaluationTrait;
use App\Livewire\Traits\Evaluations\FilterEvaluationsTrait;
use App\Livewire\Traits\Evaluations\PinEvaluationTrait;
use App\Models\SearchEvaluation;
use App\Models\SearchModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Laravel\Jetstream\RedirectsActions;
use Livewire\Component;
use Livewire\WithPagination;

class Evaluations extends Component
{
    use WithPagination;
    use RedirectsActions;
    use EditEvaluationModalTrait;
    use DeleteEvaluationTrait;
    use ExportEvaluationTrait;
    use FilterEvaluationsTrait;
    use ArchiveEvaluationTrait;
    use PinEvaluationTrait;
    use BaselineEvaluationTrait;

    public const int PER_PAGE = 10;

    protected function getListeners(): array
    {
        $teamId = Auth::user()->current_team_id;

        return [
            sprintf('echo-private:team.%d,.SearchEvaluationCreated', $teamId) => '$refresh',
            sprintf('echo-private:team.%d,.SearchEvaluationUpdated', $teamId) => '$refresh',
            sprintf('echo-private:team.%d,.SearchEvaluationDeleted', $teamId) => '$refresh',
        ];
    }

    public function render(): View
    {
        $allModels = Auth::user()->currentTeam
            ->models()
            ->with('tags')
            ->get();

        return view('livewire.pages.evaluations', [
            'evaluations' => $this->applyFilters(SearchEvaluation::query())
                ->whereHas('model', fn (Builder $query) =>
                    $query->where(SearchModel::FIELD_TEAM_ID, Auth::user()->currentTeam->id)
                )
                ->with('user', 'model.team', 'metrics', 'model.tags', 'tags')
                ->withCount('keywords')
                ->orderByDesc(SearchEvaluation::FIELD_PINNED)
                ->orderByDesc(SearchEvaluation::FIELD_ID)
                ->paginate(self::PER_PAGE),
            'allModels' => $allModels,
        ])->title('Search Evaluations');
    }
}
