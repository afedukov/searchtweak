<?php

namespace App\Livewire\Evaluations;

use App\DTO\OrderBy;
use App\Models\EvaluationMetric;
use App\Models\SearchEvaluation;
use Illuminate\View\View;
use Livewire\Attributes\Modelable;
use Livewire\Component;

class KeywordsOrderBy extends Component
{
    #[Modelable]
    public OrderBy $orderBy;

    public SearchEvaluation $evaluation;

    public array $metrics;

    public function mount(): void
    {
        $this->metrics = $this->getMetrics();
    }

    public function render(): View
    {
        return view('livewire.evaluations.keywords-order-by', [
            'label' => $this->getOrderByLabel(),
            'directions' => [
                'asc' => [
                    'label' => 'Asc',
                    'icon' => 'fas fa-sort-amount-up',
                ],
                'desc' => [
                    'label' => 'Desc',
                    'icon' => 'fas fa-sort-amount-down',
                ],
            ],
        ]);
    }

    private function getMetrics(): array
    {
        return $this->evaluation
            ->metrics
            ->mapWithKeys(function (EvaluationMetric $metric) {
                return [$metric->id => $metric->getFullyQualifiedName()];
            })
            ->all();
    }

    public function getOrderByLabel(): string
    {
        return match ($this->orderBy->getMetricId()) {
            OrderBy::ORDER_BY_KEYWORD => 'Keyword',
            OrderBy::ORDER_BY_DEFAULT => 'Default',
            default => $this->metrics[$this->orderBy->getMetricId()],
        };
    }
}
