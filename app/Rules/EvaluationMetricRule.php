<?php

namespace App\Rules;

use App\Models\EvaluationMetric;
use App\Models\SearchEvaluation;
use App\Services\Scorers\ScorerFactory;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use Illuminate\Translation\PotentiallyTranslatedString;

class EvaluationMetricRule implements DataAwareRule, ValidationRule
{
    /**
     * All the data under validation.
     *
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * Run the validation rule.
     *
     * @param Closure(string): PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $scales = [];
        $exists = [];

        try {
            if (isset($this->data['evaluation'][SearchEvaluation::FIELD_STATUS]) && $this->data['evaluation'][SearchEvaluation::FIELD_STATUS] !== SearchEvaluation::STATUS_PENDING) {
                throw new \InvalidArgumentException('Evaluation can only be updated when in pending status.');
            }

            foreach ($value as $metric) {
                if (!isset($metric[EvaluationMetric::FIELD_SCORER_TYPE])) {
                    throw new \InvalidArgumentException('Metric scorer type is required.');
                }

                if (!isset($metric[EvaluationMetric::FIELD_NUM_RESULTS])) {
                    throw new \InvalidArgumentException('Metric number of results is required.');
                }

                if (!isset(ScorerFactory::SCORER_TYPES[$metric[EvaluationMetric::FIELD_SCORER_TYPE]])) {
                    throw new \InvalidArgumentException('Invalid metric scorer type.');
                }

                if (isset($this->data['evaluation'][SearchEvaluation::FIELD_MAX_NUM_RESULTS]) && $metric[EvaluationMetric::FIELD_NUM_RESULTS] > $this->data['evaluation'][SearchEvaluation::FIELD_MAX_NUM_RESULTS]) {
                    throw new \InvalidArgumentException(
                        sprintf('Metric number of results must be less than or equal to the evaluation\'s max number of results (%d).',
                            $this->data['evaluation'][SearchEvaluation::FIELD_MAX_NUM_RESULTS]
                        )
                    );
                }

                $scales[] = ScorerFactory::create($metric[EvaluationMetric::FIELD_SCORER_TYPE])->getScale()->getType();

                if (isset($exists[$metric[EvaluationMetric::FIELD_SCORER_TYPE]][$metric[EvaluationMetric::FIELD_NUM_RESULTS]])) {
                    throw new \InvalidArgumentException('Duplicate metric found.');
                }

                $exists[$metric[EvaluationMetric::FIELD_SCORER_TYPE]][$metric[EvaluationMetric::FIELD_NUM_RESULTS]] = true;

                $numResults = $metric[EvaluationMetric::FIELD_NUM_RESULTS];
                if (!is_numeric($numResults) || $numResults < 1 || $numResults > 50) {
                    throw new \InvalidArgumentException('Metric number of results must be an integer between 1 and 50.');
                }
            }

            if (count(array_unique($scales)) > 1) {
                throw new \InvalidArgumentException('All metrics must be of the same scale type.');
            }

            $scaleType = Arr::first($scales);
            if (isset($this->data['evaluation'][SearchEvaluation::FIELD_SCALE_TYPE]) && $scaleType !== $this->data['evaluation'][SearchEvaluation::FIELD_SCALE_TYPE]) {
                throw new \InvalidArgumentException("Once created, the evaluation's scale type is fixed based on the initial metrics chosen.");
            }

        } catch (\InvalidArgumentException $e) {
            $fail($e->getMessage());
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
