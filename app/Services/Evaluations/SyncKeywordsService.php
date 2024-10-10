<?php

namespace App\Services\Evaluations;

use App\Models\EvaluationKeyword;
use App\Models\SearchEvaluation;
use App\Models\Team;
use Illuminate\Support\Collection;

class SyncKeywordsService
{
    public function syncArray(SearchEvaluation $evaluation, array $keywords): void
    {
        $this->sync($evaluation, self::getKeywordsFromArray($keywords));
    }

    public function syncString(SearchEvaluation $evaluation, string $keywords): void
    {
        $this->sync($evaluation, self::getKeywordsFromString($keywords));
    }

    private function sync(SearchEvaluation $evaluation, Collection $keywords): void
    {
        $evaluation->refresh();

        $keywords = $keywords
            ->map(fn (string $keyword) => [
                EvaluationKeyword::FIELD_KEYWORD => $keyword,
            ]);

        $keywordIdsToDelete = $evaluation->keywords
            ->whereNotIn(EvaluationKeyword::FIELD_KEYWORD, $keywords->pluck(EvaluationKeyword::FIELD_KEYWORD))
            ->modelKeys();

        $keywordsToInsert = $keywords
            ->whereNotIn(EvaluationKeyword::FIELD_KEYWORD, $evaluation->keywords->pluck(EvaluationKeyword::FIELD_KEYWORD));

        if ($keywordIdsToDelete) {
            $evaluation->keywords()->whereKey($keywordIdsToDelete)->delete();
        }

        if ($keywordsToInsert->isNotEmpty()) {
            $evaluation->keywords()->createMany($keywordsToInsert->all());
        }
    }

    /**
     * @return Collection<string>
     */
    public static function getKeywordsFromString(string $keywords): Collection
    {
        return collect(explode("\n", $keywords))
            ->map(fn (mixed $keyword) => trim((string) $keyword))
            ->filter()
            ->unique()
            ->values();
    }

    /**
     * @return Collection<string>
     */
    public static function getKeywordsFromArray(array $keywords): Collection
    {
        return collect($keywords)
            ->map(fn (mixed $keyword) => trim((string) $keyword))
            ->filter()
            ->unique()
            ->values();
    }

    public function validate(string|array $keywords, Team $team): void
    {
        if (is_array($keywords)) {
            $keywordsCount = self::getKeywordsFromArray($keywords)->count();
        } else {
            $keywordsCount = self::getKeywordsFromString($keywords)->count();
        }

        if ($keywordsCount <= 0) {
            throw new \InvalidArgumentException('Number of keywords must be greater than 0.');
        }

        if ($keywordsCount > 250) {
            throw new \InvalidArgumentException('Number of keywords exceeds the limit of 250.');
        }
    }
}
