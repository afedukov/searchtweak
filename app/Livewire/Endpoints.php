<?php

namespace App\Livewire;

use App\Actions\Endpoints\DeleteSearchEndpoint;
use App\Actions\Endpoints\ToggleSearchEndpointActive;
use App\Livewire\Traits\Endpoints\EditEndpointModalTrait;
use App\Livewire\Traits\Models\EditModelModalTrait;
use App\Livewire\Traits\Models\TestModelTrait;
use App\Models\SearchEndpoint;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Laravel\Jetstream\RedirectsActions;
use Livewire\Component;
use Livewire\WithPagination;
use Masmerise\Toaster\Toaster;

class Endpoints extends Component
{
    use WithPagination;
    use RedirectsActions;
    use EditEndpointModalTrait;
    use EditModelModalTrait;
    use TestModelTrait;

    public const int PER_PAGE = 10;

    public const string FILTER_STATUS_ALL = 'all';
    public const string FILTER_STATUS_ACTIVE = 'active';
    public const string FILTER_STATUS_ARCHIVED = 'archived';

    public const string SESSION_FILTER_STATUS = 'endpoint-filter-status';

    public bool $confirmingEndpointRemoval = false;
    public ?int $endpointIdBeingRemoved = null;

    public string $filterStatusMode = self::FILTER_STATUS_ALL;

    protected function getListeners(): array
    {
        $teamId = Auth::user()->current_team_id;

        return [
            sprintf('echo-private:team.%d,.SearchEndpointCreated', $teamId) => '$refresh',
            sprintf('echo-private:team.%d,.SearchEndpointUpdated', $teamId) => '$refresh',
            sprintf('echo-private:team.%d,.SearchEndpointDeleted', $teamId) => '$refresh',
        ];
    }

    public function mount(): void
    {
        if (Session::has(self::SESSION_FILTER_STATUS)) {
            $sessionFilterStatus = Session::get(self::SESSION_FILTER_STATUS);

            if (in_array($sessionFilterStatus, [
                self::FILTER_STATUS_ALL,
                self::FILTER_STATUS_ACTIVE,
                self::FILTER_STATUS_ARCHIVED,
            ], true)) {
                $this->filterStatusMode = $sessionFilterStatus;
            }
        }

        $this->initializeEditModel();
    }

    public function render(): View
    {
        Session::put(self::SESSION_FILTER_STATUS, $this->filterStatusMode);

        $query = Auth::user()->currentTeam
            ->endpoints()
            ->with(['user', 'team']);

        if ($this->filterStatusMode === self::FILTER_STATUS_ARCHIVED) {
            $query->whereNotNull(SearchEndpoint::FIELD_ARCHIVED_AT);
        } elseif ($this->filterStatusMode === self::FILTER_STATUS_ACTIVE) {
            $query->whereNull(SearchEndpoint::FIELD_ARCHIVED_AT);
        }

        return view('livewire.pages.endpoints', [
            'endpoints' => $query->paginate(self::PER_PAGE),
        ])->title('Search Endpoints');
    }

    public function updatedFilterStatusMode(): void
    {
        $this->resetPage();
    }

    public function resetFilter(): void
    {
        $this->filterStatusMode = self::FILTER_STATUS_ALL;
    }

    public function deleteEndpoint(DeleteSearchEndpoint $action): void
    {
        $endpoint = Auth::user()
            ->currentTeam
            ->endpoints()
            ->findOrFail($this->endpointIdBeingRemoved);

        try {
            $action->delete($endpoint);
        } catch (\Exception $e) {
            Toaster::error($e->getMessage());

            return;
        } finally {
            $this->confirmingEndpointRemoval = false;
            $this->endpointIdBeingRemoved = null;
        }
    }

    public function toggleEndpointActive(ToggleSearchEndpointActive $action, int $endpointId): void
    {
        $endpoint = Auth::user()
            ->currentTeam
            ->endpoints()
            ->findOrFail($endpointId);

        try {
            $action->toggle($endpoint);
        } catch (AuthorizationException $e) {
            Toaster::error($e->getMessage());

            return;
        }
    }
}
