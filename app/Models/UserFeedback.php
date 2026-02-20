<?php

namespace App\Models;

use App\Events\EvaluationFeedbackChangedEvent;
use App\Jobs\Evaluations\RecalculateMetricsJob;
use App\Services\Evaluations\UserFeedbackService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $user_id
 * @property int|null $judge_id
 * @property int $search_snapshot_id
 * @property int|null $grade
 * @property string|null $reason
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property User|null $user
 * @property Judge|null $judge
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
 * @method static Builder|static availableForJudge()
 * @method static Builder|static claimedByJudge(int $judgeId)
 */
class UserFeedback extends Model
{
    use HasFactory;

    public const string FIELD_ID = 'id';
    public const string FIELD_USER_ID = 'user_id';
    public const string FIELD_JUDGE_ID = 'judge_id';
    public const string FIELD_SEARCH_SNAPSHOT_ID = 'search_snapshot_id';
    public const string FIELD_GRADE = 'grade';
    public const string FIELD_REASON = 'reason';
    public const string FIELD_CREATED_AT = 'created_at';
    public const string FIELD_UPDATED_AT = 'updated_at';

    protected $table = 'user_feedbacks';

    protected $fillable = [
        self::FIELD_USER_ID,
        self::FIELD_JUDGE_ID,
        self::FIELD_SEARCH_SNAPSHOT_ID,
        self::FIELD_GRADE,
        self::FIELD_REASON,
    ];

    protected $casts = [
        self::FIELD_USER_ID => 'int',
        self::FIELD_JUDGE_ID => 'int',
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

    public function judge(): BelongsTo
    {
        return $this->belongsTo(Judge::class);
    }

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(SearchSnapshot::class, self::FIELD_SEARCH_SNAPSHOT_ID);
    }

    public function isJudgeGraded(): bool
    {
        return $this->judge_id !== null && $this->grade !== null;
    }

    public function isHumanGraded(): bool
    {
        return $this->user_id !== null && $this->grade !== null;
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
            // Human assignment (within lock window)
            ->where(fn (Builder $query) => $query
                ->whereNotNull(UserFeedback::FIELD_USER_ID)
                ->where(UserFeedback::FIELD_UPDATED_AT, '>=', Carbon::now()->subMinutes(UserFeedbackService::FEEDBACK_LOCK_TIMEOUT_MINUTES))
            )
            // Any graded feedback (human or judge)
            ->orWhereNotNull(UserFeedback::FIELD_GRADE)
            // Judge-claimed (processing or graded)
            ->orWhereNotNull(UserFeedback::FIELD_JUDGE_ID)
        );
    }

    public function scopeUnassigned(Builder $query): Builder
    {
        return $query
            ->whereNull(UserFeedback::FIELD_JUDGE_ID)
            ->where(fn (Builder $query) => $query
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

    /**
     * Feedbacks available for judge processing: ungraded, not assigned to any user or judge.
     */
    public function scopeAvailableForJudge(Builder $query): Builder
    {
        return $query
            ->whereNull(self::FIELD_GRADE)
            ->whereNull(self::FIELD_JUDGE_ID)
            ->where(function (Builder $q) {
                $q->whereNull(self::FIELD_USER_ID)
                    ->orWhere(self::FIELD_UPDATED_AT, '<', Carbon::now()->subMinutes(UserFeedbackService::FEEDBACK_LOCK_TIMEOUT_MINUTES));
            });
    }

    /**
     * Feedbacks claimed by a specific judge but not yet graded.
     */
    public function scopeClaimedByJudge(Builder $query, int $judgeId): Builder
    {
        return $query
            ->where(self::FIELD_JUDGE_ID, $judgeId)
            ->whereNull(self::FIELD_GRADE);
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
        return $this->grade === null
            && $this->judge_id === null
            && ($this->user_id === null || $this->isAssignmentExpired());
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
