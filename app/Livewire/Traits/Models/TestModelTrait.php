<?php

namespace App\Livewire\Traits\Models;

use App\DTO\ModelExecutionResult;
use App\Services\Models\ExecuteModelService;
use Illuminate\Validation\ValidationException;

trait TestModelTrait
{
    public ?array $executionResult = null;

    public function test(ExecuteModelService $executeModelService): void
    {
        try {
            $this->executionResult = $executeModelService
                ->initialize($this->modelForm->getModel())
                ->execute('')
                ->toArray();
        } catch (ValidationException $e) {
            $this->executionResult = (new ModelExecutionResult(0, 'Validation error. Please check the form.', collect()))->toArray();

            throw $e;
        }
    }
}
