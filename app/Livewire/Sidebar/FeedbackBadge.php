<?php

namespace App\Livewire\Sidebar;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class FeedbackBadge extends Component
{
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
        return view('livewire.sidebar.feedback-badge', [
            'count' => Auth::user()->getUngradedSnapshotsCount(),
        ]);
    }
}
