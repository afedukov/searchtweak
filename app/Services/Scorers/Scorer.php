<?php

namespace App\Services\Scorers;

use App\Models\EvaluationKeyword;
use App\Models\SearchSnapshot;
use App\Models\UserFeedback;
use App\Services\Scorers\Scales\BinaryScale;
use App\Services\Scorers\Scales\Scale;
use App\Services\Transformers\Transformers;
use JsonSerializable;

abstract class Scorer implements JsonSerializable
{
    protected ?Scale $scale = null;

    protected Transformers $transformers;

    protected array $settings = [];

    protected string $scaleClass = BinaryScale::class;

    abstract public function getType(): string;

    abstract public function getName(int $keywordsCount = 1): string;

    abstract public function getBriefDescription(int $keywordsCount = 1): string;

    abstract public function getDescription(): string;

    abstract public function calculate(EvaluationKeyword $keyword, int $limit): ?float;

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function getScale(): Scale
    {
        if ($this->scale === null) {
            $this->scale = new $this->scaleClass();
        }

        return $this->scale;
    }

    public function getDisplayName(int $numResults = 10, int $keywordsCount = 1): string
    {
        return sprintf($this->getName($keywordsCount), $numResults);
    }

    public function jsonSerialize(): array
    {
        return [
            'type' => $this->getType(),
            'name' => $this->getName(),
            'brief_description' => $this->getBriefDescription(),
            'description' => $this->getDescription(),
            'settings' => $this->getSettings(),
            'scale' => $this->getScale()->jsonSerialize(),
        ];
    }

    public function setTransformers(Transformers $transformers): Scorer
    {
        $this->transformers = $transformers;

        return $this;
    }

    protected function getValue(SearchSnapshot $snapshot): ?float
    {
        $grades = $snapshot->feedbacks
            ->whereNotNull(UserFeedback::FIELD_GRADE)
            ->pluck(UserFeedback::FIELD_GRADE)
            ->all();

        if ($this->transformers->isNotEmpty()) {
            $grades = array_map(fn (int $grade) => $this->transformers->transform($this->getScale()->getType(), $grade), $grades);
        }

        return $this->getScale()->getValue($grades);
    }

    protected function getRelevanceValues(EvaluationKeyword $keyword, int $limit): array
    {
        $snapshots = $keyword
            ->loadMissing('snapshots.feedbacks')
            ->snapshots
            ->take($limit);

        $relevanceValues = [];
        foreach ($snapshots as $snapshot) {
            $relevanceValues[$snapshot->position] = $this->getValue($snapshot);
        }

        return $relevanceValues;
    }
}
