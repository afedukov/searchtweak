<?php

namespace App\Models;

use App\Events\EvaluationArchivedChangedEvent;
use App\Events\EvaluationProgressChangedEvent;
use App\Events\EvaluationScaleTypeChangedEvent;
use App\Events\EvaluationStatusChangedEvent;
use App\Jobs\Evaluations\UpdatePreviousValuesJob;
use App\Livewire\Widgets\EvaluationProgressWidget;
use App\Livewire\Widgets\EvaluationWidget;
use App\Services\Evaluations\UserFeedbackService;
use App\Services\Scorers\Scales\BinaryScale;
use App\Services\Scorers\Scales\DetailScale;
use App\Services\Scorers\Scales\GradedScale;
use App\Services\Scorers\Scales\Scale;
use App\Services\Scorers\Scales\ScaleFactory;
use App\Services\Transformers\Transformers;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property int $user_id
 * @property int $model_id
 * @property string $scale_type
 * @property int $status
 * @property float $progress
 * @property string $name
 * @property string $description
 * @property array $settings
 * @property int $max_num_results
 * @property int $successful_keywords
 * @property int $failed_keywords
 * @property bool $archived
 * @property bool $pinned
 * @property Carbon $finished_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read string $status_label
 * @property-read bool $changes_blocked
 *
 * @property User $user
 * @property SearchModel $model
 * @property Collection<EvaluationKeyword> $keywords
 * @property Collection<EvaluationKeyword> $keywordsUnordered
 * @property Collection<EvaluationMetric> $metrics
 *
 * @method static Builder|static pending()
 * @method static Builder|static active()
 * @method static Builder|static finished()
 * @method static Builder|static notFinished()
 * @method static Builder|static team(int $teamId)
 */
class SearchEvaluation extends TeamBroadcastableModel implements TaggableInterface
{
    public const string FIELD_ID = 'id';
    public const string FIELD_USER_ID = 'user_id';
    public const string FIELD_MODEL_ID = 'model_id';
    public const string FIELD_SCALE_TYPE = 'scale_type';
    public const string FIELD_STATUS = 'status';
    public const string FIELD_PROGRESS = 'progress';
    public const string FIELD_NAME = 'name';
    public const string FIELD_DESCRIPTION = 'description';
    public const string FIELD_SETTINGS = 'settings';
    public const string FIELD_MAX_NUM_RESULTS = 'max_num_results';
    public const string FIELD_SUCCESSFUL_KEYWORDS = 'successful_keywords';
    public const string FIELD_FAILED_KEYWORDS = 'failed_keywords';
    public const string FIELD_ARCHIVED = 'archived';
    public const string FIELD_PINNED = 'pinned';
    public const string FIELD_FINISHED_AT = 'finished_at';
    public const string FIELD_CREATED_AT = 'created_at';
    public const string FIELD_UPDATED_AT = 'updated_at';

    public const int STATUS_PENDING = 0;
    public const int STATUS_ACTIVE = 1;
    public const int STATUS_FINISHED = 2;

    public const array STATUS_LABELS = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_FINISHED => 'Finished',
    ];

    public const array SCALE_TYPES = [
        BinaryScale::SCALE_TYPE,
        GradedScale::SCALE_TYPE,
        DetailScale::SCALE_TYPE,
    ];

    public const string SETTING_FEEDBACK_STRATEGY = 'strategy';
    public const string SETTING_SHOW_POSITION = 'position';
    public const string SETTING_REUSE_STRATEGY = 'reuse';
    public const string SETTING_AUTO_RESTART = 'auto_restart';
    public const string SETTING_TRANSFORMERS = 'transformers';

    public const int REUSE_STRATEGY_NONE = 0;
    public const int REUSE_STRATEGY_QUERY_DOC = 1;
    public const int REUSE_STRATEGY_QUERY_DOC_POSITION = 2;

    protected $fillable = [
        self::FIELD_USER_ID,
        self::FIELD_MODEL_ID,
        self::FIELD_SCALE_TYPE,
        self::FIELD_STATUS,
        self::FIELD_PROGRESS,
        self::FIELD_NAME,
        self::FIELD_DESCRIPTION,
        self::FIELD_SETTINGS,
        self::FIELD_MAX_NUM_RESULTS,
        self::FIELD_SUCCESSFUL_KEYWORDS,
        self::FIELD_FAILED_KEYWORDS,
        self::FIELD_ARCHIVED,
        self::FIELD_PINNED,
        self::FIELD_FINISHED_AT,
    ];

    protected $casts = [
        self::FIELD_USER_ID => 'int',
        self::FIELD_MODEL_ID => 'int',
        self::FIELD_STATUS => 'int',
        self::FIELD_PROGRESS => 'float',
        self::FIELD_SETTINGS => 'array',
        self::FIELD_MAX_NUM_RESULTS => 'int',
        self::FIELD_FINISHED_AT => 'datetime',
        self::FIELD_SUCCESSFUL_KEYWORDS => 'int',
        self::FIELD_FAILED_KEYWORDS => 'int',
        self::FIELD_ARCHIVED => 'bool',
        self::FIELD_PINNED => 'bool',
    ];

    private ?Scale $scale = null;

    protected function getBroadcastChannelName(): string
    {
        return sprintf('team.%d', $this->model->team_id);
    }

    public static function booted(): void
    {
        static::updated(function (self $evaluation) {
            if ($evaluation->isDirty(self::FIELD_STATUS)) {
                // Flush ungraded snapshots count cache for the whole team
                UserFeedbackService::flushUngradedSnapshotsCountCache($evaluation->model->team_id);

                EvaluationStatusChangedEvent::dispatch($evaluation);

                if ($evaluation->status === self::STATUS_FINISHED) {
                    UpdatePreviousValuesJob::dispatch($evaluation->model_id, $evaluation->id);
                }
            }

            if ($evaluation->isDirty(self::FIELD_SCALE_TYPE)) {
                EvaluationScaleTypeChangedEvent::dispatch($evaluation);
            }

            if ($evaluation->isDirty(self::FIELD_PROGRESS)) {
                EvaluationProgressChangedEvent::dispatch($evaluation);
            }

            if ($evaluation->isDirty(self::FIELD_ARCHIVED)) {
                UpdatePreviousValuesJob::dispatch($evaluation->model_id, $evaluation->id);
                EvaluationArchivedChangedEvent::dispatch($evaluation);
            }
        });

        static::deleted(function (self $evaluation) {
            UpdatePreviousValuesJob::dispatch($evaluation->model_id, $evaluation->id);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(SearchModel::class, self::FIELD_MODEL_ID);
    }

    public function keywords(): HasMany
    {
        return $this->hasMany(EvaluationKeyword::class, EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID)
            ->oldest(EvaluationKeyword::FIELD_ID);
    }

    public function keywordsUnordered(): HasMany
    {
        return $this->hasMany(EvaluationKeyword::class, EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID);
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(EvaluationMetric::class, EvaluationMetric::FIELD_SEARCH_EVALUATION_ID)
            ->oldest(EvaluationMetric::FIELD_ID);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where(self::FIELD_STATUS, self::STATUS_PENDING);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where(self::FIELD_STATUS, self::STATUS_ACTIVE);
    }

    public function scopeFinished(Builder $query): Builder
    {
        return $query->where(self::FIELD_STATUS, self::STATUS_FINISHED);
    }

    public function scopeNotFinished(Builder $query): Builder
    {
        return $query->where(self::FIELD_STATUS, '!=', self::STATUS_FINISHED);
    }

    public function scopeTeam(Builder $query, int $teamId): Builder
    {
        return $query->whereRelation('model', SearchModel::FIELD_TEAM_ID, $teamId);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isFinished(): bool
    {
        return $this->status === self::STATUS_FINISHED;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? 'Unknown';
    }

    public static function getStatusByLabel(string $label): int
    {
        $labels = array_map('strtolower', self::STATUS_LABELS);

        return array_flip($labels)[strtolower($label)] ?? throw new \InvalidArgumentException('Invalid status label');
    }

    protected static function getBlockChangesCacheKey(int $evaluationId): string
    {
        return sprintf('search-evaluation-block-changes::%d', $evaluationId);
    }

    public function blockChanges(): void
    {
        Cache::put(self::getBlockChangesCacheKey($this->id), true, now()->addMinutes(15));
    }

    public function allowChanges(): void
    {
        Cache::forget(self::getBlockChangesCacheKey($this->id));
    }

    public function getChangesBlockedAttribute(): bool
    {
        return Cache::has(self::getBlockChangesCacheKey($this->id));
    }

    public function getNumResults(): int
    {
        return $this->metrics->max(EvaluationMetric::FIELD_NUM_RESULTS);
    }

    public function showPosition(): bool
    {
        return $this->settings[self::SETTING_SHOW_POSITION] ?? false;
    }

    public function getFeedbackStrategy(): int
    {
        return $this->settings[self::SETTING_FEEDBACK_STRATEGY] ?? 1;
    }

    public function getReuseStrategy(): int
    {
        return $this->settings[self::SETTING_REUSE_STRATEGY] ?? self::REUSE_STRATEGY_NONE;
    }

    public function autoRestart(): bool
    {
        return $this->settings[self::SETTING_AUTO_RESTART] ?? false;
    }

    public function getTransformers(): Transformers
    {
        $array = $this->settings[self::SETTING_TRANSFORMERS] ?? [
            'scale_type' => $this->scale_type,
            'rules' => [],
        ];

        return Transformers::fromArray($array);
    }

    public function getScale(): Scale
    {
        if ($this->scale === null) {
            $this->scale = ScaleFactory::create($this->scale_type);
        }

        return $this->scale;
    }

    public function getTotalFeedbacks(): int
    {
        $snapshotIds = $this->getSnapshotIds();

        return UserFeedback::query()
            ->whereIn(UserFeedback::FIELD_SEARCH_SNAPSHOT_ID, $snapshotIds)
            ->count();
    }

    public function getGradedFeedbacks(): int
    {
        $snapshotIds = $this->getSnapshotIds();

        return UserFeedback::query()
            ->whereIn(UserFeedback::FIELD_SEARCH_SNAPSHOT_ID, $snapshotIds)
            ->whereNotNull(UserFeedback::FIELD_GRADE)
            ->count();
    }

    private function getSnapshotIds(): array
    {
        $keywordIds = $this->keywords()
            ->pluck(EvaluationKeyword::FIELD_ID)
            ->all();

        return SearchSnapshot::query()
            ->whereIn(SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID, $keywordIds)
            ->pluck(SearchSnapshot::FIELD_ID)
            ->all();
    }

    public function getProgressTotal(): string
    {
        $snapshotIds = $this->getSnapshotIds();

        $total = UserFeedback::query()
            ->whereIn(UserFeedback::FIELD_SEARCH_SNAPSHOT_ID, $snapshotIds)
            ->count();

        $graded = UserFeedback::query()
            ->whereIn(UserFeedback::FIELD_SEARCH_SNAPSHOT_ID, $snapshotIds)
            ->whereNotNull(UserFeedback::FIELD_GRADE)
            ->count();

        return sprintf('%d / %d', $graded, $total);
    }

    public function updateProgress(): void
    {
        $total = $this->getTotalFeedbacks();
        $totalGraded = $this->getGradedFeedbacks();

        $this->progress = $total > 0 ? ($totalGraded * 100) / $total : 0;
    }

    /**
     * Determine if the evaluation has started at least once.
     */
    public function hasStarted(): bool
    {
        return $this->max_num_results !== null;
    }

    /**
     * Determine if the evaluation can be deleted.
     */
    public function isDeletable(): bool
    {
        return $this->isFinished() || $this->isPending();
    }

    public function isArchivable(): bool
    {
        return !$this->archived;
    }

    public function isUnarchivable(): bool
    {
        return $this->archived;
    }

    public function isPinnable(): bool
    {
        return !$this->pinned;
    }

    public function isUnpinnable(): bool
    {
        return $this->pinned;
    }

    public function isBaselineable(): bool
    {
        return $this->isFinished() && !$this->isBaseline();
    }

    public function isUnbaselineable(): bool
    {
        return $this->isBaseline();
    }

    public function isBaseline(?Team $team = null): bool
    {
        $team = $team ?? Auth::user()->currentTeam;
        if ($team === null) {
            throw new \RuntimeException('Team is required to check if the evaluation is baseline');
        }

        return $team->baseline_evaluation_id === $this->id;
    }

    public function canGiveFeedback(): bool
    {
        return $this->isActive();
    }

    public function delete(): bool
    {
        UserWidget::query()
            ->whereIn(UserWidget::FIELD_WIDGET_CLASS, [EvaluationProgressWidget::class, EvaluationWidget::class])
            ->where(UserWidget::FIELD_SETTINGS . '->id', $this->id)
            ->delete();

        $this->metrics->each->delete();

        return parent::delete();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, EvaluationTag::class, EvaluationTag::FIELD_EVALUATION_ID, EvaluationTag::FIELD_TAG_ID, self::FIELD_ID, Tag::FIELD_ID)
            ->withTimestamps()
            ->orderBy(Tag::FIELD_ID);
    }
}
