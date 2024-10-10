<?php

namespace App\Livewire\Traits\Endpoints;

use App\Actions\Endpoints\CreateSearchEndpoint;
use App\Actions\Endpoints\UpdateSearchEndpoint;
use App\Livewire\Forms\EndpointForm;
use App\Models\SearchEndpoint;
use Illuminate\Auth\Access\AuthorizationException;
use Masmerise\Toaster\Toaster;

trait EditEndpointModalTrait
{
    public bool $editEndpointModal = false;

    public EndpointForm $endpointForm;

    public function createEndpoint(): void
    {
        $this->endpointForm->reset();
        $this->endpointForm->resetErrorBag();
        $this->endpointForm->endpoint = null;

        $this->editEndpointModal = true;
    }

    public function editEndpoint(SearchEndpoint $endpoint): void
    {
        $this->endpointForm->reset();
        $this->endpointForm->resetErrorBag();
        $this->endpointForm->setEndpoint($endpoint);
        $this->editEndpointModal = true;
    }

    public function cloneEndpoint(SearchEndpoint $endpoint): void
    {
        $this->endpointForm->reset();
        $this->endpointForm->resetErrorBag();
        $this->endpointForm->setEndpoint($endpoint, true);
        $this->editEndpointModal = true;
    }

    public function saveEndpoint(): void
    {
        try {
            if ($this->endpointForm->endpoint === null) {
                app(CreateSearchEndpoint::class)->create($this->endpointForm);
            } else {
                app(UpdateSearchEndpoint::class)->update($this->endpointForm);
            }
        } catch (AuthorizationException $e) {
            Toaster::error($e->getMessage());
        }

        $this->editEndpointModal = false;
    }
}
