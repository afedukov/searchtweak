<?php

namespace App\Services\Transformers;

use App\Livewire\Forms\EvaluationForm;
use App\Models\EvaluationMetric;
use App\Services\Scorers\Scales\BinaryScale;
use App\Services\Scorers\Scales\DetailScale;
use App\Services\Scorers\Scales\GradedScale;
use App\Services\Scorers\ScorerFactory;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;

class Transformers implements Arrayable
{
    /**
     * @param string $scaleType
     * @param array<string, array<int, int>> $rules
     */
    public function __construct(protected string $scaleType, protected array $rules)
    {
    }

    /**
     * Transform a value from the current scale type to given scale type.
     *
     * @param string $toScaleType
     * @param int $value
     *
     * @return int
     */
    public function transform(string $toScaleType, int $value): int
    {
        if ($toScaleType === $this->scaleType) {
            return $value;
        }

        return $this->rules[$toScaleType][$value] ??
            throw new \InvalidArgumentException(sprintf('Value %d is not in the transformer rules for scale type %s', $value, $toScaleType));
    }

    public static function getDefaultFormTransformers(): array
    {
        $rules = [
            // Binary to Graded
            self::getKey(BinaryScale::SCALE_TYPE, GradedScale::SCALE_TYPE) => [
                BinaryScale::IRRELEVANT => GradedScale::POOR,
                BinaryScale::RELEVANT => GradedScale::PERFECT,
            ],

            // Binary to Detail
            self::getKey(BinaryScale::SCALE_TYPE, DetailScale::SCALE_TYPE) => [
                BinaryScale::IRRELEVANT => DetailScale::V_1,
                BinaryScale::RELEVANT => DetailScale::V_10,
            ],

            // Graded to Binary
            self::getKey(GradedScale::SCALE_TYPE, BinaryScale::SCALE_TYPE) => [
                GradedScale::POOR => BinaryScale::IRRELEVANT,
                GradedScale::FAIR => BinaryScale::RELEVANT,
                GradedScale::GOOD => BinaryScale::RELEVANT,
                GradedScale::PERFECT => BinaryScale::RELEVANT,
            ],

            // Graded to Detail
            self::getKey(GradedScale::SCALE_TYPE, DetailScale::SCALE_TYPE) => [
                GradedScale::POOR => DetailScale::V_1,
                GradedScale::FAIR => DetailScale::V_4,
                GradedScale::GOOD => DetailScale::V_7,
                GradedScale::PERFECT => DetailScale::V_10,
            ],

            // Detail to Binary
            self::getKey(DetailScale::SCALE_TYPE, BinaryScale::SCALE_TYPE) => [
                DetailScale::V_1 => BinaryScale::IRRELEVANT,
                DetailScale::V_2 => BinaryScale::RELEVANT,
                DetailScale::V_3 => BinaryScale::RELEVANT,
                DetailScale::V_4 => BinaryScale::RELEVANT,
                DetailScale::V_5 => BinaryScale::RELEVANT,
                DetailScale::V_6 => BinaryScale::RELEVANT,
                DetailScale::V_7 => BinaryScale::RELEVANT,
                DetailScale::V_8 => BinaryScale::RELEVANT,
                DetailScale::V_9 => BinaryScale::RELEVANT,
                DetailScale::V_10 => BinaryScale::RELEVANT,
            ],

            // Detail to Graded
            self::getKey(DetailScale::SCALE_TYPE, GradedScale::SCALE_TYPE) => [
                DetailScale::V_1 => GradedScale::POOR,
                DetailScale::V_2 => GradedScale::FAIR,
                DetailScale::V_3 => GradedScale::FAIR,
                DetailScale::V_4 => GradedScale::FAIR,
                DetailScale::V_5 => GradedScale::GOOD,
                DetailScale::V_6 => GradedScale::GOOD,
                DetailScale::V_7 => GradedScale::GOOD,
                DetailScale::V_8 => GradedScale::PERFECT,
                DetailScale::V_9 => GradedScale::PERFECT,
                DetailScale::V_10 => GradedScale::PERFECT,
            ],
        ];

        return array_map(fn (array $rules) => implode("\n", array_map(fn ($from, $to) => "$from: $to", array_keys($rules), $rules)), $rules);
    }

    public function toArray(): array
    {
        return [
            'scale_type' => $this->scaleType,
            'rules' => $this->rules,
        ];
    }

    public static function fromArray(array $array): static
    {
        return new static($array['scale_type'], $array['rules'] ?? []);
    }

    public static function createFromForm(EvaluationForm $form): static
    {
        return self::create($form->metrics, $form->scale_type, $form->transformers);
    }

    public static function create(array $metrics, string $scaleType, array $transformers): static
    {
        $rules = [];

        $scaleDestinations = self::getScaleDestinations($metrics, $scaleType);

        foreach ($transformers as $key => $transformerString) {
            [$from, $to] = explode('_', $key);
            if ($from !== $scaleType || !in_array($to, $scaleDestinations)) {
                continue;
            }

            $lines = array_unique(array_filter(explode("\n", $transformerString)));

            foreach ($lines as $line) {
                $parts = explode(':', $line);
                if (count($parts) !== 2) {
                    continue;
                }

                $gradeFrom = (int) trim($parts[0]);
                $gradeTo = (int) trim($parts[1]);

                $rules[$to][$gradeFrom] = $gradeTo;
            }
        }

        return new static($scaleType, $rules);
    }

    private static function getScaleDestinations(array $metrics, ?string $exclude = null): array
    {
        $scales = [];

        foreach ($metrics as $metric) {
            $scorerType = $metric[EvaluationMetric::FIELD_SCORER_TYPE] ?? '';

            $scaleType = ScorerFactory::create($scorerType)->getScale()->getType();
            if ($scaleType !== $exclude) {
                $scales[] = $scaleType;
            }
        }

        return array_unique($scales);
    }

    /**
     * @return array<string, array<int, int>>
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    public function getScaleType(): string
    {
        return $this->scaleType;
    }

    protected static function getKey(string $from, string $to): string
    {
        return sprintf('%s_%s', $from, $to);
    }

    public function toFormArray(): array
    {
        $transformers = [];

        foreach ($this->rules as $to => $rules) {
            $transformer = '';

            foreach ($rules as $fromValue => $toValue) {
                $transformer .= sprintf("%d: %d\n", $fromValue, $toValue);
            }

            $key = self::getKey($this->scaleType, $to);

            $transformers[$key] = trim($transformer);
        }

        return $transformers;
    }

    public function isEmpty(): bool
    {
        return empty($this->rules);
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * Determine if this Transformer is equal to another.
     *
     * @param Transformers $other
     * @return bool
     */
    public function equals(Transformers $other): bool
    {
        if ($other->isEmpty() && $this->isEmpty()) {
            return true;
        }

        $dottedRules = Arr::dot($this->getRules());
        ksort($dottedRules);

        $dottedRulesOther = Arr::dot($other->getRules());
        ksort($dottedRulesOther);

        return $this->scaleType === $other->scaleType && $dottedRules === $dottedRulesOther;
    }
}
