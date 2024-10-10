<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $evaluation_keyword_id
 * @property int $evaluation_metric_id
 * @property float $value
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property EvaluationKeyword $keyword
 * @property EvaluationMetric $metric
 */
class KeywordMetric extends Model
{
    public const string FIELD_ID = 'id';
    public const string FIELD_EVALUATION_KEYWORD_ID = 'evaluation_keyword_id';
    public const string FIELD_EVALUATION_METRIC_ID = 'evaluation_metric_id';
    public const string FIELD_VALUE = 'value';
    public const string FIELD_CREATED_AT = 'created_at';
    public const string FIELD_UPDATED_AT = 'updated_at';

    protected $fillable = [
        self::FIELD_EVALUATION_KEYWORD_ID,
        self::FIELD_EVALUATION_METRIC_ID,
        self::FIELD_VALUE,
    ];

    protected $casts = [
        self::FIELD_EVALUATION_KEYWORD_ID => 'int',
        self::FIELD_EVALUATION_METRIC_ID => 'int',
        self::FIELD_VALUE => 'float',
    ];

    /**
     * @return BelongsTo
     */
    public function keyword(): BelongsTo
    {
        return $this->belongsTo(EvaluationKeyword::class, self::FIELD_EVALUATION_KEYWORD_ID);
    }

    /**
     * @return BelongsTo
     */
    public function metric(): BelongsTo
    {
        return $this->belongsTo(EvaluationMetric::class, self::FIELD_EVALUATION_METRIC_ID);
    }
}
