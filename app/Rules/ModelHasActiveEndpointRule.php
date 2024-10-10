<?php

namespace App\Rules;

use App\Models\SearchEndpoint;
use App\Models\SearchModel;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Translation\PotentiallyTranslatedString;

class ModelHasActiveEndpointRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param Closure(string): PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $hasActiveEndpoint = SearchModel::query()
            ->whereKey($value)
            ->whereHas('endpoint', fn (Builder $query) => $query->whereNull(SearchEndpoint::FIELD_ARCHIVED_AT))
            ->exists();

        if (!$hasActiveEndpoint) {
            $fail('Model does not have an active endpoint.');
        }
    }
}
