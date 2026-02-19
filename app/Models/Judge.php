<?php

namespace App\Models;

use App\Services\Scorers\Scales\BinaryScale;
use App\Services\Scorers\Scales\DetailScale;
use App\Services\Scorers\Scales\GradedScale;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property int $team_id
 * @property string $name
 * @property string $description
 * @property string $provider
 * @property string $model_name
 * @property string $api_key
 * @property string|null $prompt_binary
 * @property string|null $prompt_graded
 * @property string|null $prompt_detail
 * @property array|null $settings
 * @property Carbon|null $archived_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property User $user
 * @property Team $team
 * @property Collection<UserFeedback> $feedbacks
 * @property Collection<JudgeLog> $logs
 * @property Collection<Tag> $tags
 *
 * @method static Builder|static active()
 */
class Judge extends TeamBroadcastableModel implements TaggableInterface
{
    use HasFactory;

    public const string FIELD_ID = 'id';
    public const string FIELD_USER_ID = 'user_id';
    public const string FIELD_TEAM_ID = 'team_id';
    public const string FIELD_NAME = 'name';
    public const string FIELD_DESCRIPTION = 'description';
    public const string FIELD_PROVIDER = 'provider';
    public const string FIELD_MODEL_NAME = 'model_name';
    public const string FIELD_API_KEY = 'api_key';
    public const string FIELD_PROMPT_BINARY = 'prompt_binary';
    public const string FIELD_PROMPT_GRADED = 'prompt_graded';
    public const string FIELD_PROMPT_DETAIL = 'prompt_detail';
    public const string FIELD_SETTINGS = 'settings';
    public const string FIELD_ARCHIVED_AT = 'archived_at';
    public const string FIELD_CREATED_AT = 'created_at';
    public const string FIELD_UPDATED_AT = 'updated_at';

    public const string PROVIDER_OPENAI = 'openai';
    public const string PROVIDER_ANTHROPIC = 'anthropic';
    public const string PROVIDER_GOOGLE = 'google';

    public const array VALID_PROVIDERS = [
        self::PROVIDER_OPENAI,
        self::PROVIDER_ANTHROPIC,
        self::PROVIDER_GOOGLE,
    ];

    public const string SETTING_BATCH_SIZE = 'batch_size';
    public const string SETTING_MODEL_PARAMS = 'model_params';

    public const array PROMPTS = [
        BinaryScale::SCALE_TYPE => self::FIELD_PROMPT_BINARY,
        GradedScale::SCALE_TYPE => self::FIELD_PROMPT_GRADED,
        DetailScale::SCALE_TYPE => self::FIELD_PROMPT_DETAIL,
    ];

    public const int DEFAULT_BATCH_SIZE = 5;

    protected $fillable = [
        self::FIELD_NAME,
        self::FIELD_USER_ID,
        self::FIELD_TEAM_ID,
        self::FIELD_DESCRIPTION,
        self::FIELD_PROVIDER,
        self::FIELD_MODEL_NAME,
        self::FIELD_API_KEY,
        self::FIELD_PROMPT_BINARY,
        self::FIELD_PROMPT_GRADED,
        self::FIELD_PROMPT_DETAIL,
        self::FIELD_SETTINGS,
        self::FIELD_ARCHIVED_AT,
    ];

    protected $casts = [
        self::FIELD_USER_ID => 'int',
        self::FIELD_TEAM_ID => 'int',
        self::FIELD_API_KEY => 'encrypted',
        self::FIELD_SETTINGS => 'array',
        self::FIELD_ARCHIVED_AT => 'datetime',
    ];

    protected $hidden = [
        self::FIELD_API_KEY,
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

    public function feedbacks(): HasMany
    {
        return $this->hasMany(UserFeedback::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(JudgeLog::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, JudgeTag::class, JudgeTag::FIELD_JUDGE_ID, JudgeTag::FIELD_TAG_ID, self::FIELD_ID, Tag::FIELD_ID)
            ->withTimestamps()
            ->orderBy(Tag::FIELD_ID);
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

    public static function getDefaultPrompt(string $scaleType): string
    {
        return file_get_contents(resource_path("prompts/judge/{$scaleType}.md"));
    }

    public function getBatchSize(): int
    {
        return $this->settings[self::SETTING_BATCH_SIZE] ?? self::DEFAULT_BATCH_SIZE;
    }

    public function getModelParams(): array
    {
        return $this->settings[self::SETTING_MODEL_PARAMS] ?? [];
    }

    /**
     * Determine whether this judge is eligible to process the given evaluation
     * based on tag matching rules (same AND logic as for users).
     */
    public static function matchesEvaluation(self $judge, SearchEvaluation $evaluation): bool
    {
        $evaluationTags = $evaluation->relationLoaded('tags')
            ? $evaluation->tags
            : $evaluation->load('tags')->tags;

        if ($evaluationTags->isEmpty()) {
            return true;
        }

        $judgeTags = $judge->relationLoaded('tags')
            ? $judge->tags
            : $judge->load('tags')->tags;

        if ($judgeTags->isEmpty()) {
            return false;
        }

        return $evaluationTags->pluck(Tag::FIELD_ID)->diff($judgeTags->pluck(Tag::FIELD_ID))->isEmpty();
    }

    /**
     * Get the prompt template for the given scale type.
     */
    public function getPromptForScale(string $scaleType): string
    {
        $field = self::PROMPTS[$scaleType] ?? null;

        if ($field === null) {
            throw new \InvalidArgumentException(sprintf('Unknown scale type: %s', $scaleType));
        }

        return $this->{$field} ?? self::getDefaultPrompt($scaleType);
    }

    public function toArray(): array
    {
        return parent::toArray() + [
            'active' => $this->isActive(),
        ];
    }
}
