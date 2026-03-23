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
        $days = $this->period;

        return view('livewire.superuser.dashboard', [
            'overview' => $service->getOverviewStats(),
            'userRegistrations' => $service->getUserRegistrations($days),
            'feedbacksGraded' => $service->getFeedbacksGraded($days),
            'metricsDistribution' => $service->getMetricsDistribution($days),
            'evaluationsByScale' => $service->getEvaluationsByScale($days),
            'feedbackStats' => $service->getFeedbackStats($days),
            'judgeSuccessRate' => $service->getJudgeSuccessRateByProvider($days),
            'avgLatency' => $service->getAvgLatencyByDay($days),
            'tokenUsage' => $service->getTokenUsageStats($days),
            'topTeams' => $service->getTopTeams(),
            'recentEvaluations' => $service->getRecentEvaluations(),
        ])->title('Admin: Dashboard');
    }
}
