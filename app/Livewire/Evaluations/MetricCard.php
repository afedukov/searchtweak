<?php

namespace App\Livewire\Evaluations;

use App\Livewire\Traits\ControlWidgetTrait;
use App\Livewire\Widgets\EvaluationMetricWidget;
use App\Models\EvaluationMetric;
use Illuminate\View\View;
use Livewire\Component;

/**
 * @property string $name
 * @property string $description
 * @property string $scaleType
 */
class MetricCard extends Component
{
    use ControlWidgetTrait {
        attach as protected baseAttach;
    }

    public EvaluationMetric $metric;

    public int $keywordsCount = 1;

    public bool $attached = false;

    public function getWidgetClass(): string
    {
        return EvaluationMetricWidget::class;
    }

    public function getWidgetEntityId(): int
    {
        return $this->metric->id;
    }

    public function attach(): void
    {
        $this->baseAttach();
        $this->attached = !$this->attached;
    }

    public function render(): View
    {
        $scorer = $this->metric->getScorer();

        return view('livewire.evaluations.metric-card', [
            'name' => $scorer->getDisplayName($this->metric->num_results, $this->keywordsCount),
            'description' => $scorer->getBriefDescription($this->keywordsCount),
            'scaleType' => $scorer->getScale()->getType(),
            'attached' => $this->attached,
        ]);
    }
}
