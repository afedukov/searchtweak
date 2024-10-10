<?php

namespace App\Models;

use App\Livewire\Widgets\EvaluationMetricWidget;
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
 * @property int $num_results
 * @property array $settings
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
    public const string FIELD_NUM_RESULTS = 'num_results';
    public const string FIELD_SETTINGS = 'settings';
    public const string FIELD_CREATED_AT = 'created_at';
    public const string FIELD_UPDATED_AT = 'updated_at';

    protected $fillable = [
        self::FIELD_SEARCH_EVALUATION_ID,
        self::FIELD_SCORER_TYPE,
        self::FIELD_VALUE,
        self::FIELD_NUM_RESULTS,
        self::FIELD_SETTINGS,
    ];

    protected $casts = [
        self::FIELD_SEARCH_EVALUATION_ID => 'int',
        self::FIELD_VALUE => 'float',
        self::FIELD_NUM_RESULTS => 'int',
        self::FIELD_SETTINGS => 'array',
    ];

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
}
