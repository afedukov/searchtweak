<?php

namespace App\Livewire\Traits\Models;

use App\Actions\Models\CreateSearchModel;
use App\Actions\Models\UpdateSearchModel;
use App\Livewire\Forms\ModelForm;
use App\Models\SearchEndpoint;
use App\Models\SearchModel;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Masmerise\Toaster\Toaster;

trait EditModelModalTrait
{
    use TestModelTrait;

    public bool $editModelModal = false;

    public ModelForm $modelForm;

    public Collection $modelFormEndpoints;

    protected function initializeEditModel(): void
    {
        $this->modelFormEndpoints = Auth::user()->currentTeam
            ->endpoints()
            ->get()
            ->keyBy(SearchEndpoint::FIELD_ID);
    }

    public function createModel(): void
    {
        $this->modelForm->reset();
        $this->modelForm->resetErrorBag();
        $this->modelForm->model = null;

        $this->executionResult = null;
        $this->editModelModal = true;
    }

    public function editModel(SearchModel $model): void
    {
        $this->modelForm->reset();
        $this->modelForm->resetErrorBag();
        $this->modelForm->setModel($model);

        $this->executionResult = null;
        $this->editModelModal = true;
    }

    public function cloneModel(SearchModel $model): void
    {
        $this->modelForm->reset();
        $this->modelForm->resetErrorBag();
        $this->modelForm->setModel($model, true);

        $this->executionResult = null;
        $this->editModelModal = true;
    }

    public function saveModel(): void
    {
        try {
            if ($this->modelForm->model === null) {
                app(CreateSearchModel::class)->create($this->modelForm);
            } else {
                app(UpdateSearchModel::class)->update($this->modelForm);
            }
        } catch (AuthorizationException $e) {
            Toaster::error($e->getMessage());
        }

        $this->editModelModal = false;
    }
}
