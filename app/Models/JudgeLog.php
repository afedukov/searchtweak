<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $judge_id
 * @property int|null $search_evaluation_id
 * @property string $provider
 * @property string $model
 * @property int|null $http_status_code
 * @property string $request_url
 * @property string $request_body
 * @property string|null $response_body
 * @property string|null $error_message
 * @property int|null $latency_ms
 * @property int|null $prompt_tokens
 * @property int|null $completion_tokens
 * @property int|null $total_tokens
 * @property int|null $batch_size
 * @property string|null $scale_type
 *
 * @property Judge|null $judge
 * @property SearchEvaluation|null $evaluation
 *
 * @method static Builder|static successful()
 * @method static Builder|static failed()
 */
class JudgeLog extends Model
{
    public const string FIELD_ID = 'id';
    public const string FIELD_JUDGE_ID = 'judge_id';
    public const string FIELD_TEAM_ID = 'team_id';
    public const string FIELD_SEARCH_EVALUATION_ID = 'search_evaluation_id';
    public const string FIELD_PROVIDER = 'provider';
    public const string FIELD_MODEL = 'model';
    public const string FIELD_HTTP_STATUS_CODE = 'http_status_code';
    public const string FIELD_REQUEST_URL = 'request_url';
    public const string FIELD_REQUEST_BODY = 'request_body';
    public const string FIELD_RESPONSE_BODY = 'response_body';
    public const string FIELD_ERROR_MESSAGE = 'error_message';
    public const string FIELD_LATENCY_MS = 'latency_ms';
    public const string FIELD_PROMPT_TOKENS = 'prompt_tokens';
    public const string FIELD_COMPLETION_TOKENS = 'completion_tokens';
    public const string FIELD_TOTAL_TOKENS = 'total_tokens';
    public const string FIELD_BATCH_SIZE = 'batch_size';
    public const string FIELD_SCALE_TYPE = 'scale_type';
    public const string FIELD_CREATED_AT = 'created_at';
    public const string FIELD_UPDATED_AT = 'updated_at';

    protected $fillable = [
        self::FIELD_JUDGE_ID,
        self::FIELD_TEAM_ID,
        self::FIELD_SEARCH_EVALUATION_ID,
        self::FIELD_PROVIDER,
        self::FIELD_MODEL,
        self::FIELD_HTTP_STATUS_CODE,
        self::FIELD_REQUEST_URL,
        self::FIELD_REQUEST_BODY,
        self::FIELD_RESPONSE_BODY,
        self::FIELD_ERROR_MESSAGE,
        self::FIELD_LATENCY_MS,
        self::FIELD_PROMPT_TOKENS,
        self::FIELD_COMPLETION_TOKENS,
        self::FIELD_TOTAL_TOKENS,
        self::FIELD_BATCH_SIZE,
        self::FIELD_SCALE_TYPE,
    ];

    protected $casts = [
        self::FIELD_JUDGE_ID => 'int',
        self::FIELD_TEAM_ID => 'int',
        self::FIELD_SEARCH_EVALUATION_ID => 'int',
        self::FIELD_HTTP_STATUS_CODE => 'int',
        self::FIELD_LATENCY_MS => 'int',
        self::FIELD_PROMPT_TOKENS => 'int',
        self::FIELD_COMPLETION_TOKENS => 'int',
        self::FIELD_TOTAL_TOKENS => 'int',
        self::FIELD_BATCH_SIZE => 'int',
    ];

    public function judge(): BelongsTo
    {
        return $this->belongsTo(Judge::class);
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(SearchEvaluation::class, self::FIELD_SEARCH_EVALUATION_ID);
    }

    public function isSuccessful(): bool
    {
        return $this->error_message === null
            && $this->http_status_code !== null
            && $this->http_status_code >= 200
            && $this->http_status_code < 300;
    }

    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->whereNull(self::FIELD_ERROR_MESSAGE)
            ->whereBetween(self::FIELD_HTTP_STATUS_CODE, [200, 299]);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNotNull(self::FIELD_ERROR_MESSAGE)
                ->orWhere(self::FIELD_HTTP_STATUS_CODE, '<', 200)
                ->orWhere(self::FIELD_HTTP_STATUS_CODE, '>=', 300);
        });
    }
}
