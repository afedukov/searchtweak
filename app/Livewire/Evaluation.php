<?php

namespace App\Livewire;

use App\Actions\Evaluations\FinishSearchEvaluation;
use App\DTO\OrderBy;
use App\Livewire\Traits\Evaluations\EditEvaluationModalTrait;
use App\Livewire\Traits\Evaluations\ExportEvaluationTrait;
use App\Livewire\Widgets\EvaluationWidget;
use App\Models\EvaluationKeyword;
use App\Models\KeywordMetric;
use App\Models\SearchEvaluation;
use App\Models\UserWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Laravel\Jetstream\RedirectsActions;
use Livewire\Component;
use Livewire\WithPagination;
use Toaster;

class Evaluation extends Component
{
    use RedirectsActions;
    use EditEvaluationModalTrait;
    use ExportEvaluationTrait;
    use WithPagination;

    public const int PER_PAGE = 5;

    public SearchEvaluation $evaluation;

    public bool $confirmingEvaluationFinish = false;

    public string $query = '';

    public OrderBy $orderBy;

    public function mount(SearchEvaluation $evaluation): void
    {
        $this->evaluation = $evaluation
            ->load([
                'user',
                'model.team',
                'metrics.keywordMetrics',
                'model.tags',
                'tags',
            ]);

        $this->orderBy = new OrderBy();
    }

    public function render(): View
    {
        $attached = Auth::user()->widgets()
            ->where(UserWidget::FIELD_WIDGET_CLASS, EvaluationWidget::class)
            ->where(UserWidget::FIELD_SETTINGS . '->id', $this->evaluation->id)
            ->exists();

        $query = $this->evaluation
            ->keywordsUnordered()
            ->when($this->query, fn (Builder $query) => $query->where(EvaluationKeyword::FIELD_KEYWORD, 'like', "%{$this->query}%"))
            ->getQuery()
            ->orderByDesc(EvaluationKeyword::FIELD_FAILED);

        $keywords = $this->applyOrderBy($query)
            ->with('snapshots.feedbacks.user')
            ->paginate(self::PER_PAGE);

        return view('livewire.pages.evaluation', [
            'attached' => $attached,
            'keywords' => $keywords,
        ])->title($this->evaluation->name);
    }

    public function finish(FinishSearchEvaluation $action): void
    {
        try {
            Gate::authorize('finish', $this->evaluation);

            $action->finish($this->evaluation, false);
        } catch (\Exception $e) {
            Toaster::error($e->getMessage());

            return;
        } finally {
            $this->confirmingEvaluationFinish = false;
        }
    }

    public function attach(): void
    {
        $widget = Auth::user()->widgets()
            ->where(UserWidget::FIELD_WIDGET_CLASS, EvaluationWidget::class)
            ->where(UserWidget::FIELD_SETTINGS . '->id', $this->evaluation->id)
            ->first();

        if ($widget instanceof UserWidget) {
            $this->detachWidget($widget);
        } else {
            $this->attachWidget();
        }
    }

    private function attachWidget(): void
    {
        Auth::user()->attachWidget(EvaluationWidget::class, ['id' => $this->evaluation->id]);

        Toaster::success('Evaluation added to dashboard.');
    }

    private function detachWidget(UserWidget $widget): void
    {
        $widget->delete();

        Toaster::success('Evaluation removed from dashboard.');
    }

    private function applyOrderBy(Builder $query): Builder
    {
        $metricId = $this->orderBy->getMetricId();

        if ($metricId === OrderBy::ORDER_BY_KEYWORD) {
            // Order by keyword
            return $query->orderBy(EvaluationKeyword::FIELD_KEYWORD, $this->orderBy->getDirection());
        } elseif ($metricId === OrderBy::ORDER_BY_DEFAULT) {
            // Order by ID (default)
            return $query->orderBy(EvaluationKeyword::FIELD_ID, $this->orderBy->getDirection());
        } else {
            // Order by metric value
            return $query
                ->leftJoin('keyword_metrics', fn (JoinClause $join) =>
                    $join->on('evaluation_keywords.' . EvaluationKeyword::FIELD_ID, '=', 'keyword_metrics.' . KeywordMetric::FIELD_EVALUATION_KEYWORD_ID)
                        ->where('keyword_metrics.' . KeywordMetric::FIELD_EVALUATION_METRIC_ID, '=', $metricId)
                )
                ->orderBy('keyword_metrics.' . KeywordMetric::FIELD_VALUE, $this->orderBy->getDirection())
                ->orderBy('evaluation_keywords.' . EvaluationKeyword::FIELD_ID)
                ->select('evaluation_keywords.*');
        }
    }
}
