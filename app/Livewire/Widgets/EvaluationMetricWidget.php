<?php

namespace App\Livewire\Widgets;

use App\Models\EvaluationMetric;
use Illuminate\View\View;

class EvaluationMetricWidget extends BaseWidget
{
    public EvaluationMetric $metric;
    public string $name;
    public string $description;
    public string $scaleType;

    public static function getWidgetName(array $data = null): string
    {
        $metric = EvaluationMetric::find($data['id']);
        if ($metric === null) {
            return 'Evaluation Metric';
        }

        $keywordsCount = $metric->evaluation->keywords()->count();

        return sprintf('%s: %s', $metric->getFullyQualifiedName($keywordsCount), $metric->evaluation->name);
    }

    public static function isRemovable(): bool
    {
        return true;
    }

    protected function getListeners(): array
    {
        return [
            sprintf('echo-private:search-evaluation.%s,.evaluation.status.changed', $this->metric->search_evaluation_id) => '$refresh',
        ];
    }

    public function mount(): void
    {
        $metric = EvaluationMetric::find($this->widget['settings']['id']);
        if ($metric === null) {
            $this->skipRender();
            return;
        }

        $this->metric = $metric;

        $scorer = $this->metric->getScorer();

        $keywordsCount = $metric->evaluation->keywords()->count();

        $this->name = $scorer->getDisplayName($this->metric->num_results, $keywordsCount);
        $this->description = $scorer->getBriefDescription($keywordsCount);
        $this->scaleType = $scorer->getScale()->getType();
    }

    public function render(): View
    {
        return view('livewire.widgets.evaluation-metric-widget');
    }
}
