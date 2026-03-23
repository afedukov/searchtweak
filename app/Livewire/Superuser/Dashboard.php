<?php

namespace App\Livewire\Superuser;

use App\Services\Superuser\DashboardService;
use Illuminate\View\View;
use Livewire\Component;

class Dashboard extends Component
{
    public int $period = 30;

    public function updatedPeriod(): void
    {
        $this->dispatch('period-changed');
    }

    public function render(DashboardService $service): View
    {
        return view('livewire.superuser.dashboard', [
            'overview' => $service->getOverviewStats(),
            'userRegistrations' => $service->getUserRegistrations($this->period),
            'evaluationsCreated' => $service->getEvaluationsCreated($this->period),
            'evaluationsByStatus' => $service->getEvaluationsByStatus(),
            'evaluationsByScale' => $service->getEvaluationsByScale(),
            'feedbackStats' => $service->getFeedbackStats(),
            'judgeSuccessRate' => $service->getJudgeSuccessRateByProvider(),
            'avgLatency' => $service->getAvgLatencyByDay($this->period),
            'tokenUsage' => $service->getTokenUsageStats(),
            'topTeams' => $service->getTopTeams(),
            'recentEvaluations' => $service->getRecentEvaluations(),
        ])->title('Admin: Dashboard');
    }
}
