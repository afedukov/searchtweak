<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $user_id
 * @property int $team_id
 * @property int $type
 * @property string $name
 * @property string $url
 * @property string $method
 * @property string $description
 * @property array $headers
 * @property int $mapper_type
 * @property string $mapper_code
 * @property array $settings
 * @property Carbon|null $archived_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read string $url_shortened
 *
 * @property User $user
 * @property Team $team
 * @property Collection<SearchModel> $models
 *
 * @method static Builder|static active()
 */
class SearchEndpoint extends TeamBroadcastableModel
{
    public const string FIELD_ID = 'id';
    public const string FIELD_USER_ID = 'user_id';
    public const string FIELD_TEAM_ID = 'team_id';
    public const string FIELD_TYPE = 'type';
    public const string FIELD_NAME = 'name';
    public const string FIELD_URL = 'url';
    public const string FIELD_METHOD = 'method';
    public const string FIELD_DESCRIPTION = 'description';
    public const string FIELD_HEADERS = 'headers';
    public const string FIELD_MAPPER_TYPE = 'mapper_type';
    public const string FIELD_MAPPER_CODE = 'mapper_code';
    public const string FIELD_SETTINGS = 'settings';
    public const string FIELD_ARCHIVED_AT = 'archived_at';
    public const string FIELD_CREATED_AT = 'created_at';
    public const string FIELD_UPDATED_AT = 'updated_at';

    public const int TYPE_SEARCH_API = 1;

    public const int MAPPER_TYPE_DOT_ARRAY = 1;

    public const array VALID_METHODS = ['GET', 'POST', 'PUT'];

    public const string SETTING_MULTI_THREADING = 'mt';

    public const int MULTI_THREADING_AUTO = 0;
    public const int MULTI_THREADING_SINGLE = 1;

    protected $fillable = [
        self::FIELD_NAME,
        self::FIELD_USER_ID,
        self::FIELD_TEAM_ID,
        self::FIELD_TYPE,
        self::FIELD_URL,
        self::FIELD_METHOD,
        self::FIELD_DESCRIPTION,
        self::FIELD_HEADERS,
        self::FIELD_MAPPER_TYPE,
        self::FIELD_MAPPER_CODE,
        self::FIELD_SETTINGS,
        self::FIELD_ARCHIVED_AT,
    ];

    protected $casts = [
        self::FIELD_USER_ID => 'int',
        self::FIELD_TEAM_ID => 'int',
        self::FIELD_TYPE => 'int',
        self::FIELD_HEADERS => 'array',
        self::FIELD_MAPPER_TYPE => 'int',
        self::FIELD_SETTINGS => 'array',
        self::FIELD_ARCHIVED_AT => 'datetime',
    ];

    public static function booted(): void
    {
        static::saving(function (SearchEndpoint $endpoint) {
            $method = Str::upper($endpoint->method);

            if (!in_array($method, self::VALID_METHODS)) {
                throw new \InvalidArgumentException('Invalid method');
            }

            $endpoint->method = $method;
        });
    }

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

    public function models(): HasMany
    {
        return $this->hasMany(SearchModel::class, SearchModel::FIELD_ENDPOINT_ID, self::FIELD_ID)
            ->latest(SearchModel::FIELD_ID);
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function isActive(): bool
    {
        return !$this->isArchived();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull(self::FIELD_ARCHIVED_AT);
    }

    public function getMethodBadgeClass(): string
    {
        return match ($this->method) {
            'POST' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
            'PUT' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
            default => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        };
    }

    public function getUrlShortenedAttribute(): string
    {
        $url = $this->url;

        $maxLength = 40;
        $minStartLength = 20;
        $minEndLength = 20;

        // If the URL is already short enough, return it as is
        if (mb_strlen($url) <= $maxLength) {
            return $url;
        }

        // Take the required characters from the beginning and end of the URL
        $start = mb_substr($url, 0, $minStartLength);
        $end = mb_substr($url, -$minEndLength);

        // Return the shortened URL with "..." in the middle
        return $start . '...' . $end;
    }

    public function delete(): bool
    {
        foreach ($this->models as $model) {
            if ($model->evaluations->where(SearchEvaluation::FIELD_STATUS, '!=', SearchEvaluation::STATUS_FINISHED)->isNotEmpty()) {
                throw new \RuntimeException('Cannot delete endpoint with unfinished evaluations.');
            }
        }

        $this->models->each->delete();

        return parent::delete();
    }

    public function toArray(): array
    {
        return parent::toArray() + [
            'active' => $this->isActive(),
        ];
    }

    public function getMultiThreadingSetting(): int
    {
        return $this->settings[self::SETTING_MULTI_THREADING] ?? self::MULTI_THREADING_AUTO;
    }

    public function getExecutionQueue(): string
    {
        return match ($this->getMultiThreadingSetting()) {
            self::MULTI_THREADING_SINGLE => 'snapshots-single',
            default => 'snapshots-auto',
        };
    }
}
