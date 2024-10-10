<?php

namespace App\Livewire\Widgets;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class GiveFeedbackWidget extends BaseWidget
{
    public static function getWidgetName(array $data = null): string
    {
        return 'Give Feedback';
    }

    public static function isRemovable(): bool
    {
        return false;
    }

    protected function getListeners(): array
    {
        return [
            sprintf('echo-private:team.%d,.evaluation.feedback.changed', Auth::user()->current_team_id) => '$refresh',
            sprintf('echo-private:team.%d,.SearchEvaluationUpdated', Auth::user()->current_team_id) => '$refresh',
            sprintf('echo-private:App.Models.User.%d,.user.tags.changed', Auth::id()) => '$refresh',
        ];
    }

    public function render(): View
    {
        return view('livewire.widgets.give-feedback-widget', [
            'ungradedSnapshotsCount' => Auth::user()->getUngradedSnapshotsCount(),
        ]);
    }
}
