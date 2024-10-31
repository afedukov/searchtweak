<?php

namespace App\Models;

use App\DTO\MetricChange;
use App\Livewire\Widgets\EvaluationMetricWidget;
use App\Services\Metrics\PreviousEvaluationMetricService;
use App\Services\Scorers\Scorer;
use App\Services\Scorers\ScorerFactory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $search_evaluation_id
 * @property string $scorer_type
 * @property float $value
 * @property float|null $previous_value
 * @property int $num_results
 * @property array $settings
 * @property Carbon $finished_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property SearchEvaluation $evaluation
 * @property Collection<KeywordMetric> $keywordMetrics
 * @property Collection<MetricValue> $values
 */
class EvaluationMetric extends Model
{
    public const string FIELD_ID = 'id';
    public const string FIELD_SEARCH_EVALUATION_ID = 'search_evaluation_id';
    public const string FIELD_SCORER_TYPE = 'scorer_type';
    public const string FIELD_VALUE = 'value';
    public const string FIELD_PREVIOUS_VALUE = 'previous_value';
    public const string FIELD_NUM_RESULTS = 'num_results';
    public const string FIELD_SETTINGS = 'settings';
    public const string FIELD_FINISHED_AT = 'finished_at';
    public const string FIELD_CREATED_AT = 'created_at';
    public const string FIELD_UPDATED_AT = 'updated_at';

    protected $fillable = [
        self::FIELD_SEARCH_EVALUATION_ID,
        self::FIELD_SCORER_TYPE,
        self::FIELD_VALUE,
        self::FIELD_PREVIOUS_VALUE,
        self::FIELD_NUM_RESULTS,
        self::FIELD_SETTINGS,
        self::FIELD_FINISHED_AT,
    ];

    protected $casts = [
        self::FIELD_SEARCH_EVALUATION_ID => 'int',
        self::FIELD_VALUE => 'float',
        self::FIELD_PREVIOUS_VALUE => 'float',
        self::FIELD_NUM_RESULTS => 'int',
        self::FIELD_SETTINGS => 'array',
        self::FIELD_FINISHED_AT => 'datetime',
    ];

    public static function booted(): void
    {
        static::created(function (EvaluationMetric $metric) {
            $metric->syncPreviousValue();
        });

        static::updated(function (EvaluationMetric $metric) {
            if ($metric->isDirty(EvaluationMetric::FIELD_NUM_RESULTS)) {
                $metric->syncPreviousValue();
            }
        });
    }

    public function syncPreviousValue(): void
    {
        $this->previous_value = $this->getPreviousMetric()?->value;
        $this->saveQuietly();
    }

    /**
     * @return BelongsTo
     */
    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(SearchEvaluation::class, self::FIELD_SEARCH_EVALUATION_ID);
    }

    /**
     * @return Scorer
     */
    public function getScorer(): Scorer
    {
        return ScorerFactory::create($this->scorer_type);
    }

    /**
     * @param EvaluationKeyword $keyword
     *
     * @return float|null
     */
    public function calculate(EvaluationKeyword $keyword): ?float
    {
        if ($this->search_evaluation_id !== $keyword->search_evaluation_id) {
            throw new \InvalidArgumentException('The metric and keyword must belong to the same evaluation.');
        }

        return $this->getScorer()->calculate($keyword, $this->num_results);
    }

    /**
     * @return HasMany
     */
    public function keywordMetrics(): HasMany
    {
        return $this->hasMany(KeywordMetric::class, KeywordMetric::FIELD_EVALUATION_METRIC_ID);
    }

    /**
     * @return HasMany
     */
    public function values(): HasMany
    {
        return $this->hasMany(MetricValue::class, MetricValue::FIELD_EVALUATION_METRIC_ID)
            ->orderByDesc(MetricValue::FIELD_ID);
    }

    public function getLastValues(int $limit = 20): array
    {
        return $this->values()
            ->take($limit)
            ->get()
            ->reverse()
            ->map(fn (MetricValue $value) => [
                'label' => $value->created_at->format('Y-m-d H:i'),
                'value' => $value->value,
            ])
            ->values()
            ->all();
    }

    public function getFullyQualifiedName(int $keywordsCount = 1): string
    {
        return $this->getScorer()->getDisplayName($this->num_results, $keywordsCount);
    }

    public function delete(): bool
    {
        // Delete "Evaluation Metric" widgets associated with this metric
        UserWidget::query()
            ->where(UserWidget::FIELD_WIDGET_CLASS, EvaluationMetricWidget::class)
            ->where(UserWidget::FIELD_SETTINGS . '->id', $this->id)
            ->delete();

        return parent::delete();
    }

    public function touchFinishedAt(): void
    {
        $this->finished_at = Carbon::now();
        $this->saveQuietly();
    }

    public function getPreviousMetric(): ?EvaluationMetric
    {
        return app(PreviousEvaluationMetricService::class)->getPrevious($this);
    }

    private function getMetricChange(?float $value, ?float $previousValue): ?MetricChange
    {
        if ($value === null || $previousValue === null) {
            return null;
        }

        $showChangeValue = true;

        if ($previousValue == 0) {
            // if the previous value is 0, we don't want to show a change percentage, only show the change arrow
            $showChangeValue = false;
            $change = $value == 0 ? 0 : 1;
        } else {
            $change = (int) round(($value - $previousValue) / $previousValue * 100);
        }

        return new MetricChange($change, $showChangeValue);
    }

    public function getChange(?SearchEvaluation $baseline = null): ?MetricChange
    {
        if ($baseline === null) {
            return $this->getMetricChange($this->value, $this->previous_value);
        }

        $baselineMetric = $baseline->metrics
            ->where(EvaluationMetric::FIELD_SCORER_TYPE, $this->scorer_type)
            ->where(EvaluationMetric::FIELD_NUM_RESULTS, $this->num_results)
            ->first();

        if ($baselineMetric) {
            return $this->getMetricChange($this->value, $baselineMetric->value);
        }

        return null;
    }
}
