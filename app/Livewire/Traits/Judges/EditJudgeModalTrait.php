<?php

namespace App\Livewire\Traits\Judges;

use App\Actions\Judges\CreateJudge;
use App\Actions\Judges\UpdateJudge;
use App\Livewire\Forms\JudgeForm;
use App\Models\Judge;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Masmerise\Toaster\Toaster;

trait EditJudgeModalTrait
{
    public bool $editJudgeModal = false;

    public JudgeForm $judgeForm;

    public function createJudge(): void
    {
        $this->judgeForm->reset();
        $this->judgeForm->resetErrorBag();
        $this->judgeForm->judge = null;
        $this->judgeForm->initDefaults();

        $this->editJudgeModal = true;
    }

    public function editJudge(Judge $judge): void
    {
        $judge = Auth::user()->currentTeam->judges()->findOrFail($judge->id);

        $this->judgeForm->reset();
        $this->judgeForm->resetErrorBag();
        $this->judgeForm->setJudge($judge);
        $this->editJudgeModal = true;
    }

    public function cloneJudge(Judge $judge): void
    {
        $judge = Auth::user()->currentTeam->judges()->findOrFail($judge->id);

        $this->judgeForm->reset();
        $this->judgeForm->resetErrorBag();
        $this->judgeForm->setJudge($judge, true);
        $this->editJudgeModal = true;
    }

    public function saveJudge(): void
    {
        try {
            if ($this->judgeForm->judge === null) {
                app(CreateJudge::class)->create($this->judgeForm);
            } else {
                app(UpdateJudge::class)->update($this->judgeForm);
            }
        } catch (AuthorizationException $e) {
            Toaster::error($e->getMessage());
        }

        $this->editJudgeModal = false;
    }
}
