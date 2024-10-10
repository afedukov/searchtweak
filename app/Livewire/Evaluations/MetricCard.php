<?php

namespace App\Livewire\Evaluations;

use App\Livewire\Traits\ControlWidgetTrait;
use App\Livewire\Widgets\EvaluationMetricWidget;
use App\Models\EvaluationMetric;
use App\Models\UserWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

/**
 * @property string $name
 * @property string $description
 * @property string $scaleType
 */
class MetricCard extends Component
{
    use ControlWidgetTrait;

    public EvaluationMetric $metric;

    public int $keywordsCount = 1;

    public function getWidgetClass(): string
    {
        return EvaluationMetricWidget::class;
    }

    public function getWidgetEntityId(): int
    {
        return $this->metric->id;
    }

    public function render(): View
    {
        $scorer = $this->metric->getScorer();

        $attached = Auth::user()->widgets()
            ->where(UserWidget::FIELD_WIDGET_CLASS, EvaluationMetricWidget::class)
            ->where(UserWidget::FIELD_SETTINGS . '->id', $this->metric->id)
            ->exists();

        return view('livewire.evaluations.metric-card', [
            'name' => $scorer->getDisplayName($this->metric->num_results, $this->keywordsCount),
            'description' => $scorer->getBriefDescription($this->keywordsCount),
            'scaleType' => $scorer->getScale()->getType(),
            'attached' => $attached,
        ]);
    }
}
