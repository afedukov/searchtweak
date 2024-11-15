<?php

namespace App\Rules;

use App\Models\EvaluationMetric;
use App\Models\SearchEvaluation;
use App\Services\Scorers\Scales\BinaryScale;
use App\Services\Scorers\Scales\DetailScale;
use App\Services\Scorers\Scales\GradedScale;
use App\Services\Scorers\Scales\ScaleFactory;
use App\Services\Scorers\ScorerFactory;
use App\Services\Transformers\Transformers;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class EvaluationTransformersRule implements DataAwareRule, ValidationRule
{
    /**
     * All the data under validation.
     *
     * @var array<string, mixed>
     */
    protected array $data = [];

    public function __construct(private readonly bool $isApi = false)
    {
    }

    /**
     * Run the validation rule.
     *
     * @param Closure(string): PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $scaleType = $this->data['scale_type'] ?? '';
        $metrics = $this->data['metrics'] ?? [];

        if (empty($scaleType)) {
            $fail('Evaluation scale type is required.');

            return;
        }

        if ($this->isApi) {
            if (!isset($value['scale_type']) || !in_array($value['scale_type'], [BinaryScale::SCALE_TYPE, DetailScale::SCALE_TYPE, GradedScale::SCALE_TYPE])) {
                $fail('Invalid transformers scale type.');

                return;
            }

            $transformers = Transformers::fromArray($value);
        } else {
            $transformers = Transformers::create($metrics, $scaleType, $value);
        }

        if ($transformers->getScaleType() !== $scaleType) {
            $fail('Transformers scale type must match the evaluation scale type.');
        }

        $transformersScaleTypes = array_keys($transformers->getRules());

        $scales = [];
        foreach ($metrics as $metric) {
            $scales[] = ScorerFactory::create($metric[EvaluationMetric::FIELD_SCORER_TYPE])->getScale()->getType();
        }

        $requiredScales = array_diff($scales, [$scaleType]);

        if (array_diff($requiredScales, $transformersScaleTypes)) {
            $fail('Transformers must contain rules for all required scales.');
        }

        if (array_diff($transformersScaleTypes, $requiredScales)) {
            $fail('Transformers contain rules for scales that are not required.');
        }

        $scaleFrom = ScaleFactory::create($scaleType);

        foreach ($transformers->getRules() as $toScaleType => $rules) {
            $scaleTo = ScaleFactory::create($toScaleType);
            $grades = array_keys($rules);

            if (array_diff($grades, $scaleFrom->getGrades()) || array_diff($scaleFrom->getGrades(), $grades)) {
                $fail('Transformer grades must match the source scale grades.');
            }

            if (array_diff($rules, $scaleTo->getGrades())) {
                $fail('Transformer rules must match the destination scale grades.');
            }
        }

        $evaluation = SearchEvaluation::find($this->data['evaluation'][SearchEvaluation::FIELD_ID] ?? null);

        if ($evaluation instanceof SearchEvaluation && $evaluation->hasStarted() && !$transformers->equals($evaluation->getTransformers())) {
            $fail('Evaluation transformers cannot be changed after it has started.');
        }
    }

    /**
     * Set the data under validation.
     *
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }
}
