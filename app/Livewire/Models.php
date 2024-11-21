<?php

namespace App\Livewire;

use App\Actions\Models\DeleteSearchModel;
use App\Livewire\Traits\Endpoints\EditEndpointModalTrait;
use App\Livewire\Traits\Evaluations\EditEvaluationModalTrait;
use App\Livewire\Traits\Models\EditModelModalTrait;
use App\Models\SearchEndpoint;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Laravel\Jetstream\RedirectsActions;
use Livewire\Component;
use Livewire\WithPagination;
use Masmerise\Toaster\Toaster;

class Models extends Component
{
    use WithPagination;
    use RedirectsActions;
    use EditModelModalTrait;
    use EditEvaluationModalTrait;
    use EditEndpointModalTrait;

    public const int PER_PAGE = 10;

    public bool $confirmingModelRemoval = false;
    public ?int $modelIdBeingRemoved = null;

    public int $filterTagId = 0;

    protected function getListeners(): array
    {
        $teamId = Auth::user()->current_team_id;

        return [
            sprintf('echo-private:team.%d,.SearchModelCreated', $teamId) => '$refresh',
            sprintf('echo-private:team.%d,.SearchModelUpdated', $teamId) => '$refresh',
            sprintf('echo-private:team.%d,.SearchModelDeleted', $teamId) => '$refresh',
            sprintf('echo-private:team.%d,.baseline.evaluation.changed', $teamId) => '$refresh',
        ];
    }

    public function mount(): void
    {
        $this->initializeEditModel();
    }

    public function render(): View
    {
        return view('livewire.pages.models', [
            'models' => Auth::user()->currentTeam
                ->models()
                ->when($this->filterTagId, fn (Builder $query) =>
                    $query->whereHas('tags', fn (Builder $query) => $query->whereKey($this->filterTagId))
                )
                ->with(['user', 'team', 'endpoint.team', 'tags'])
                ->paginate(self::PER_PAGE),
            'allModels' => Auth::user()->currentTeam
                ->models()
                ->with('tags')
                ->whereHas('endpoint', fn (Builder $query) => $query->whereNull(SearchEndpoint::FIELD_ARCHIVED_AT))
                ->get(),
        ])->title('Search Models');
    }

    public function deleteModel(DeleteSearchModel $action): void
    {
        $model = Auth::user()
            ->currentTeam
            ->models()
            ->findOrFail($this->modelIdBeingRemoved);

        try {
            $action->delete($model);
        } catch (\Exception $e) {
            Toaster::error($e->getMessage());

            return;
        } finally {
            $this->confirmingModelRemoval = false;
            $this->modelIdBeingRemoved = null;
        }

        Toaster::success('Model removed successfully.');
    }
}
