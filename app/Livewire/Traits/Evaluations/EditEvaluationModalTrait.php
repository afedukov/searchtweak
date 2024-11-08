<?php

namespace App\Livewire\Traits\Evaluations;

use App\Actions\Evaluations\CreateSearchEvaluation;
use App\Actions\Evaluations\UpdateSearchEvaluation;
use App\Livewire\Forms\EvaluationForm;
use App\Models\SearchEvaluation;
use App\Services\Transformers\Transformers;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Masmerise\Toaster\Toaster;

trait EditEvaluationModalTrait
{
    public bool $editEvaluationModal = false;

    public EvaluationForm $evaluationForm;

    public function createEvaluation(): void
    {
        $this->evaluationForm->reset();
        $this->evaluationForm->resetErrorBag();
        $this->evaluationForm->evaluation = null;
        $this->evaluationForm->transformers = Transformers::getDefaultFormTransformers();

        $this->editEvaluationModal = true;
    }

    public function editEvaluation(SearchEvaluation $evaluation): void
    {
        $this->evaluationForm->reset();
        $this->evaluationForm->resetErrorBag();
        $this->evaluationForm->setEvaluation($evaluation);
        $this->editEvaluationModal = true;
    }

    public function cloneEvaluation(SearchEvaluation $evaluation): void
    {
        $this->evaluationForm->reset();
        $this->evaluationForm->resetErrorBag();
        $this->evaluationForm->setEvaluation($evaluation, true);
        $this->editEvaluationModal = true;
    }

    public function saveEvaluation(): void
    {
        try {
            if ($this->evaluationForm->evaluation === null) {
                app(CreateSearchEvaluation::class)->create($this->evaluationForm);
                $message = 'Evaluation created successfully.';
            } else {
                app(UpdateSearchEvaluation::class)->update($this->evaluationForm);
                $message = 'Evaluation updated successfully.';
            }
        } catch (AuthorizationException $e) {
            Toaster::error($e->getMessage());
            $this->editEvaluationModal = false;

            return;
        }

        $this->editEvaluationModal = false;

        Toaster::success($message);
    }
}
