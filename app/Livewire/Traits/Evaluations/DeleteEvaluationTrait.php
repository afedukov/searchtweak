<?php

namespace App\Livewire\Traits\Evaluations;

use App\Actions\Evaluations\DeleteSearchEvaluation;
use App\Models\SearchEvaluation;
use Illuminate\Support\Facades\Gate;
use Masmerise\Toaster\Toaster;

trait DeleteEvaluationTrait
{
    public bool $confirmingEvaluationRemoval = false;
    public ?int $evaluationIdBeingRemoved = null;

    public function deleteEvaluation(DeleteSearchEvaluation $action): void
    {
        $evaluation = SearchEvaluation::query()
            ->findOrFail($this->evaluationIdBeingRemoved);

        try {
            Gate::authorize('delete', $evaluation);

            $action->delete($evaluation);
        } catch (\Exception $e) {
            Toaster::error($e->getMessage());
        } finally {
            $this->confirmingEvaluationRemoval = false;
            $this->evaluationIdBeingRemoved = null;
        }
    }
}
