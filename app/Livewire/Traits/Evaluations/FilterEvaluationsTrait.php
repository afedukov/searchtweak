<?php

namespace App\Livewire\Traits\Evaluations;

use App\Livewire\Evaluations\FilterStatus;
use App\Models\SearchEvaluation;
use Illuminate\Database\Eloquent\Builder;

trait FilterEvaluationsTrait
{
    public array $filterStatus = FilterStatus::DEFAULT_FILTER_STATUS;

    public string $filterArchived = 'current';

    public int $filterTagId = 0;

    public string $query = '';

    protected function applyFilters(Builder $query): Builder
    {
        $query->whereIn(SearchEvaluation::FIELD_STATUS, $this->filterStatus)
            ->when($this->filterTagId, fn (Builder $query) =>
                $query->whereHas('tags', fn (Builder $query) => $query->whereKey($this->filterTagId))
            );

        if ($this->query) {
            $query->where(fn (Builder $query) => $query
                ->where(SearchEvaluation::FIELD_NAME, 'like', '%' . $this->query . '%')
                ->orWhere(SearchEvaluation::FIELD_DESCRIPTION, 'like', '%' . $this->query . '%')
            );
        }

        if ($this->filterArchived === 'archived') {
            $query->where(SearchEvaluation::FIELD_ARCHIVED, true);
        } elseif ($this->filterArchived === 'current') {
            $query->where(SearchEvaluation::FIELD_ARCHIVED, false);
        }

        return $query;
    }
}
