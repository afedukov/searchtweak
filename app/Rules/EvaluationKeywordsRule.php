<?php

namespace App\Rules;

use App\Models\Team;
use App\Services\Evaluations\SyncKeywordsService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Translation\PotentiallyTranslatedString;

class EvaluationKeywordsRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param Closure(string): PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (Auth::user() instanceof Team) {
            $team = Auth::user();
        } else {
            $team = Auth::user()->currentTeam;
        }

        try {
            app(SyncKeywordsService::class)->validate($value, $team);
        } catch (\Exception $e) {
            $fail($e->getMessage());
        }
    }
}
