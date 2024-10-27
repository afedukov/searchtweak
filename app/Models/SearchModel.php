<?php

namespace App\Models;

use App\Livewire\Widgets\ModelWidget;
use App\Services\Models\ModelMetric;
use App\Services\Models\ModelMetricsBuilder;
use App\Services\Models\RequestHeadersService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property int $team_id
 * @property int $endpoint_id
 * @property string $name
 * @property string $description
 * @property array $headers
 * @property array $params
 * @property string $body
 * @property int $body_type
 * @property array $settings
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property User $user
 * @property Team $team
 * @property SearchEndpoint $endpoint
 * @property Collection<SearchEvaluation> $evaluations
 */
class SearchModel extends TeamBroadcastableModel implements TaggableInterface
{
    public const string FIELD_ID = 'id';
    public const string FIELD_USER_ID = 'user_id';
    public const string FIELD_TEAM_ID = 'team_id';
    public const string FIELD_ENDPOINT_ID = 'endpoint_id';
    public const string FIELD_NAME = 'name';
    public const string FIELD_DESCRIPTION = 'description';
    public const string FIELD_HEADERS = 'headers';
    public const string FIELD_PARAMS = 'params';
    public const string FIELD_BODY = 'body';
    public const string FIELD_BODY_TYPE = 'body_type';
    public const string FIELD_SETTINGS = 'settings';
    public const string FIELD_CREATED_AT = 'created_at';
    public const string FIELD_UPDATED_AT = 'updated_at';

    public const int BODY_TYPE_JSON = 1;
    public const int BODY_TYPE_TEXT = 2;
    public const int BODY_TYPE_XML = 3;
    public const int BODY_TYPE_HTML = 4;
    public const int BODY_TYPE_JAVASCRIPT = 5;
    public const int BODY_TYPE_FORM = 6;

    public const string SETTING_KEYWORDS = 'keywords';

    public const array DEFAULT_HEADERS = [
        'Accept' => 'application/json',
        'User-Agent' => 'SearchTweak/1.0',
    ];

    protected $fillable = [
        self::FIELD_NAME,
        self::FIELD_USER_ID,
        self::FIELD_TEAM_ID,
        self::FIELD_ENDPOINT_ID,
        self::FIELD_DESCRIPTION,
        self::FIELD_HEADERS,
        self::FIELD_PARAMS,
        self::FIELD_BODY,
        self::FIELD_BODY_TYPE,
        self::FIELD_SETTINGS,
    ];

    protected $casts = [
        self::FIELD_USER_ID => 'int',
        self::FIELD_TEAM_ID => 'int',
        self::FIELD_ENDPOINT_ID => 'int',
        self::FIELD_HEADERS => 'array',
        self::FIELD_PARAMS => 'array',
        self::FIELD_BODY_TYPE => 'int',
        self::FIELD_SETTINGS => 'array',
    ];

    protected function getBroadcastChannelName(): string
    {
        return sprintf('team.%d', $this->team_id);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(SearchEndpoint::class);
    }

    public function getHeaders(): array
    {
        return $this->headers + $this->endpoint->headers;
    }

    public function getHiddenHeaders(): array
    {
        $headers = [];

        if ($this->body_type !== null) {
            $headers += RequestHeadersService::getContentTypeHeader($this->body_type);
        }

        return $headers + self::DEFAULT_HEADERS;
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(SearchEvaluation::class, SearchEvaluation::FIELD_MODEL_ID)
            ->orderByDesc(SearchEvaluation::FIELD_ID);
    }

    public function delete(): bool
    {
        if ($this->evaluations->where(SearchEvaluation::FIELD_STATUS, '!=', SearchEvaluation::STATUS_FINISHED)->isNotEmpty()) {
            throw new \RuntimeException('Cannot delete model with unfinished evaluations.');
        }

        UserWidget::query()
            ->where(UserWidget::FIELD_WIDGET_CLASS, ModelWidget::class)
            ->where(UserWidget::FIELD_SETTINGS . '->id', $this->id)
            ->delete();

        $this->evaluations->each->delete();

        return parent::delete();
    }

    public function canCreateEvaluations(): bool
    {
        return $this->endpoint->isActive();
    }

    /**
     * @return array<ModelMetric>
     */
    public function getMetrics(): array
    {
        return app(ModelMetricsBuilder::class)->getMetrics($this);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, ModelTag::class, ModelTag::FIELD_MODEL_ID, ModelTag::FIELD_TAG_ID, self::FIELD_ID, Tag::FIELD_ID)
            ->withTimestamps()
            ->orderBy(Tag::FIELD_ID);
    }

    /**
     * @return array<string>
     */
    public function getKeywords(): array
    {
        return $this->settings[self::SETTING_KEYWORDS] ?? [];
    }

    public function getKeywordsString(): string
    {
        return implode("\n", $this->getKeywords());
    }
}
