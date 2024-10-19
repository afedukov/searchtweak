<?php

namespace App\Models;

use App\Events\EvaluationFeedbackChangedEvent;
use App\Jobs\Evaluations\RecalculateMetricsJob;
use App\Services\Evaluations\UserFeedbackService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $search_snapshot_id
 * @property int $grade
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property User $user
 * @property SearchSnapshot $snapshot
 *
 * @method static Builder|static globalPool(User $user)
 * @method static Builder|static evaluationPool(int $evaluationId)
 * @method static Builder|static assignedTo(int $userId)
 * @method static Builder|static assignedOrGraded()
 * @method static Builder|static unassigned()
 * @method static Builder|static ungraded()
 * @method static Builder|static graded()
 * @method static Builder|static team(int $teamId)
 */
class UserFeedback extends Model
{
    public const string FIELD_ID = 'id';
    public const string FIELD_USER_ID = 'user_id';
    public const string FIELD_SEARCH_SNAPSHOT_ID = 'search_snapshot_id';
    public const string FIELD_GRADE = 'grade';
    public const string FIELD_CREATED_AT = 'created_at';
    public const string FIELD_UPDATED_AT = 'updated_at';

    protected $table = 'user_feedbacks';

    protected $fillable = [
        self::FIELD_USER_ID,
        self::FIELD_SEARCH_SNAPSHOT_ID,
        self::FIELD_GRADE,
    ];

    protected $casts = [
        self::FIELD_USER_ID => 'int',
        self::FIELD_SEARCH_SNAPSHOT_ID => 'int',
        self::FIELD_GRADE => 'int',
    ];

    public static function booted(): void
    {
        static::updated(function (UserFeedback $feedback) {
            if ($feedback->isDirty(self::FIELD_GRADE)) {
                RecalculateMetricsJob::dispatch($feedback->snapshot->keyword->id);
            }

            if ($feedback->isDirty()) {
                // Flush ungraded snapshots count cache for the whole team
                UserFeedbackService::flushUngradedSnapshotsCountCache($feedback->snapshot->keyword->evaluation->model->team_id);

                // Dispatch event
                EvaluationFeedbackChangedEvent::dispatch($feedback);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(SearchSnapshot::class, self::FIELD_SEARCH_SNAPSHOT_ID);
    }

    public function scopeTeam(Builder $query, int $teamId): Builder
    {
        return $query->whereHas('snapshot', fn (Builder $query) =>
            $query->whereHas('keyword', fn (Builder $query) =>
                $query->whereHas('evaluation', fn (Builder $query) =>
                    $query->whereRelation('model', SearchModel::FIELD_TEAM_ID, $teamId)
                )
            )
        );
    }

    public function scopeGlobalPool(Builder $query, User $user): Builder
    {
        return $query->whereHas('snapshot', fn (Builder $query) =>
            $query->whereHas('keyword', fn (Builder $query) =>
                $query->whereHas('evaluation', fn (Builder $query) =>
                    $query->where(SearchEvaluation::FIELD_STATUS, SearchEvaluation::STATUS_ACTIVE)
                        ->whereRelation('model', SearchModel::FIELD_TEAM_ID, $user->current_team_id)
                )
            )
        );
    }

    public function scopeEvaluationPool(Builder $query, int $evaluationId): Builder
    {
        return $query->whereHas('snapshot', fn (Builder $query) =>
            $query->whereRelation('keyword', EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID, $evaluationId)
        );
    }

    public function scopeAssignedTo(Builder $query, int $userId): Builder
    {
        return $query->where(UserFeedback::FIELD_USER_ID, $userId)
            ->where(UserFeedback::FIELD_UPDATED_AT, '>=', Carbon::now()->subMinutes(UserFeedbackService::FEEDBACK_LOCK_TIMEOUT_MINUTES));
    }

    public function scopeAssignedOrGraded(Builder $query): Builder
    {
        return $query->where(fn (Builder $query) => $query
            ->where(fn (Builder $query) => $query
                ->whereNotNull(UserFeedback::FIELD_USER_ID)
                ->where(UserFeedback::FIELD_UPDATED_AT, '>=', Carbon::now()->subMinutes(UserFeedbackService::FEEDBACK_LOCK_TIMEOUT_MINUTES))
            )
            ->orWhereNotNull(UserFeedback::FIELD_GRADE)
        );
    }

    public function scopeUnassigned(Builder $query): Builder
    {
        return $query->where(fn (Builder $query) => $query
            ->whereNull(UserFeedback::FIELD_USER_ID)
            ->orWhere(UserFeedback::FIELD_UPDATED_AT, '<', Carbon::now()->subMinutes(UserFeedbackService::FEEDBACK_LOCK_TIMEOUT_MINUTES))
        );
    }

    public function scopeUngraded(Builder $query): Builder
    {
        return $query->whereNull(UserFeedback::FIELD_GRADE);
    }

    public function scopeGraded(Builder $query): Builder
    {
        return $query->whereNotNull(UserFeedback::FIELD_GRADE);
    }

    public function isAssignmentExpired(): bool
    {
        return $this->updated_at->diffInMinutes() > UserFeedbackService::FEEDBACK_LOCK_TIMEOUT_MINUTES;
    }

    public function isUngradedAssignedTo(int $userId): bool
    {
        return $this->grade === null && $this->user_id === $userId && !$this->isAssignmentExpired();
    }

    public function isUngradedUnassigned(): bool
    {
        return $this->grade === null && ($this->user_id === null || $this->isAssignmentExpired());
    }

    public function isAvailableTo(User $user): bool
    {
        $evaluationTags = $this->snapshot->keyword->evaluation->tags;
        if ($evaluationTags->isEmpty()) {
            return true;
        }

        $userTags = $user->getTeamTags($user->current_team_id);
        if ($userTags->isEmpty()) {
            return false;
        }

        // Check if user has all evaluation tags (AND logic)
        return $evaluationTags->diff($userTags)->isEmpty();
    }
}
