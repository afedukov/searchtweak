<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $search_evaluation_id
 * @property string $keyword
 * @property int $total_count
 * @property int $execution_code
 * @property string $execution_message
 * @property bool $failed
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property SearchEvaluation $evaluation
 * @property Collection<SearchSnapshot> $snapshots
 * @property Collection<KeywordMetric> $keywordMetrics
 */
class EvaluationKeyword extends Model
{
    public const string FIELD_ID = 'id';
    public const string FIELD_SEARCH_EVALUATION_ID = 'search_evaluation_id';
    public const string FIELD_KEYWORD = 'keyword';
    public const string FIELD_TOTAL_COUNT = 'total_count';
    public const string FIELD_EXECUTION_CODE = 'execution_code';
    public const string FIELD_EXECUTION_MESSAGE = 'execution_message';
    public const string FIELD_FAILED = 'failed';
    public const string FIELD_CREATED_AT  = 'created_at';
    public const string FIELD_UPDATED_AT = 'updated_at';

    public const int TOTAL_COUNT_UNKNOWN = -1;

    protected $fillable = [
        self::FIELD_SEARCH_EVALUATION_ID,
        self::FIELD_KEYWORD,
        self::FIELD_TOTAL_COUNT,
        self::FIELD_EXECUTION_CODE,
        self::FIELD_EXECUTION_MESSAGE,
        self::FIELD_FAILED,
    ];

    protected $casts = [
        self::FIELD_SEARCH_EVALUATION_ID => 'int',
        self::FIELD_TOTAL_COUNT => 'int',
        self::FIELD_EXECUTION_CODE => 'int',
        self::FIELD_FAILED => 'bool',
    ];

    /**
     * @return BelongsTo
     */
    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(SearchEvaluation::class, self::FIELD_SEARCH_EVALUATION_ID);
    }

    /**
     * @return HasMany
     */
    public function snapshots(): HasMany
    {
        return $this->hasMany(SearchSnapshot::class, SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID)
            ->orderBy(SearchSnapshot::FIELD_POSITION);
    }

    /**
     * For Admins. Includes snapshots that need feedback and already assigned to someone.
     *
     * @param int $userId
     *
     * @return int
     */
    public function getUngradedSnapshotsCount(int $userId): int
    {
        return $this->snapshots
            ->filter(fn (SearchSnapshot $snapshot) => $snapshot->needsFeedback() && !$snapshot->hasFeedback($userId))
            ->count();
    }

    /**
     * @return HasMany
     */
    public function keywordMetrics(): HasMany
    {
        return $this->hasMany(KeywordMetric::class, KeywordMetric::FIELD_EVALUATION_KEYWORD_ID);
    }

    public function isFailed(): bool
    {
        return $this->execution_code === 0 || $this->execution_code >= 300;
    }
}
