<?php

namespace App\Livewire;

use App\Actions\Evaluations\ResetUserFeedback;
use App\Models\EvaluationKeyword;
use App\Models\SearchEvaluation;
use App\Models\SearchSnapshot;
use App\Models\User;
use App\Models\UserFeedback;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Laravel\Jetstream\RedirectsActions;
use Livewire\Component;
use Livewire\WithPagination;
use Toaster;

class Feedbacks extends Component
{
    use WithPagination;
    use RedirectsActions;

    public const int PER_PAGE = 10;

    public SearchEvaluation $evaluation;

    public string $query = '';

    public int $filterTagId = 0;

    protected function getListeners(): array
    {
        return [
            sprintf('echo-private:search-evaluation.%s,.evaluation.feedback.changed', $this->evaluation->id) => '$refresh',
        ];
    }

    public function mount(SearchEvaluation $evaluation): void
    {
        $this->evaluation = $evaluation
            ->load('user', 'model.team');
    }

    public function render(): View
    {
        $query = UserFeedback::evaluationPool($this->evaluation->id)
            ->assignedOrGraded();

        return view('livewire.pages.feedbacks', [
            'feedbacks' => $this->applyFilters($query)
                ->select(['*'])
                ->selectRaw('CASE WHEN grade IS NULL THEN 0 ELSE 1 END as is_graded')
                ->with([
                    'user.tags',
                    'snapshot.keyword',
                ])
                ->orderBy(UserFeedback::FIELD_UPDATED_AT)
                ->orderByDesc('is_graded')
                ->paginate(self::PER_PAGE),
        ])->title(sprintf('User Feedback: %s', $this->evaluation->name));
    }

    private function applyFilters(Builder $query): Builder
    {
        if ($this->query) {
            $query->where(fn (Builder $query) => $query
                ->whereHas('snapshot', fn (Builder $query) =>
                    $query->where(fn (Builder $query) => $query
                        ->whereHas('keyword', fn (Builder $query) =>
                            $query->where(EvaluationKeyword::FIELD_KEYWORD, 'like', '%' . $this->query . '%')
                        )
                        ->orWhere(SearchSnapshot::FIELD_DOC_ID, 'like', '%' . $this->query . '%')
                    )
                )
                ->orWhereHas('user', fn (Builder $query) =>
                    $query->where(User::FIELD_NAME, 'like', '%' . $this->query . '%')
                )
            );
        }

        $query->when($this->filterTagId, fn (Builder $query) =>
            $query->whereHas('user.tags', fn (Builder $query) => $query->whereKey($this->filterTagId))
        );

        return $query;
    }

    public function resetFeedback(UserFeedback $feedback, ResetUserFeedback $action): void
    {
        try {
            $action->reset($this->evaluation, $feedback);
        } catch (\Exception $e) {
            Toaster::error($e->getMessage());
        }
    }
}
