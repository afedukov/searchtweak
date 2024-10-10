<?php

namespace App\Livewire;

use App\Actions\Evaluations\FinishSearchEvaluation;
use App\Livewire\Traits\Evaluations\EditEvaluationModalTrait;
use App\Livewire\Traits\Evaluations\ExportEvaluationTrait;
use App\Livewire\Widgets\EvaluationWidget;
use App\Models\EvaluationKeyword;
use App\Models\SearchEvaluation;
use App\Models\UserWidget;
use Illuminate\Database\Eloquent\Builder;
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
    }

    public function render(): View
    {
        $attached = Auth::user()->widgets()
            ->where(UserWidget::FIELD_WIDGET_CLASS, EvaluationWidget::class)
            ->where(UserWidget::FIELD_SETTINGS . '->id', $this->evaluation->id)
            ->exists();

        return view('livewire.pages.evaluation', [
            'attached' => $attached,
            'keywords' => $this->evaluation
                ->keywordsUnordered()
                ->when($this->query, fn (Builder $query) => $query->where(EvaluationKeyword::FIELD_KEYWORD, 'like', "%{$this->query}%"))
                ->with('snapshots.feedbacks.user')
                ->orderByDesc(EvaluationKeyword::FIELD_FAILED)
                ->orderBy(EvaluationKeyword::FIELD_ID)
                ->paginate(self::PER_PAGE),
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
}
