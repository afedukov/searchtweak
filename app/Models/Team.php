<?php

namespace App\Models;

use App\Events\BaselineEvaluationChangedEvent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Laravel\Jetstream\Events\TeamCreated;
use Laravel\Jetstream\Events\TeamDeleted;
use Laravel\Jetstream\Events\TeamUpdated;
use Laravel\Jetstream\Jetstream;
use Laravel\Jetstream\Team as JetstreamTeam;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property bool $personal_team
 * @property int $baseline_evaluation_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property User $owner
 * @property Collection<User> $users
 * @property Collection<SearchEndpoint> $endpoints
 * @property Collection<SearchModel> $models
 * @property Collection<TeamInvitation> $teamInvitations
 * @property Collection<Tag> $tags
 * @property SearchEvaluation $baseline
 */
class Team extends JetstreamTeam
{
    use HasFactory, HasApiTokens;

    public const string FIELD_ID = 'id';
    public const string FIELD_USER_ID = 'user_id';
    public const string FIELD_NAME = 'name';
    public const string FIELD_PERSONAL_TEAM = 'personal_team';
    public const string FIELD_BASELINE_EVALUATION_ID = 'baseline_evaluation_id';
    public const string FIELD_CREATED_AT = 'created_at';
    public const string FIELD_UPDATED_AT = 'updated_at';

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        self::FIELD_PERSONAL_TEAM => 'boolean',
        self::FIELD_BASELINE_EVALUATION_ID => 'int',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        self::FIELD_NAME,
        self::FIELD_PERSONAL_TEAM,
        self::FIELD_BASELINE_EVALUATION_ID,
    ];

    /**
     * The event map for the model.
     *
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'created' => TeamCreated::class,
        'updated' => TeamUpdated::class,
        'deleted' => TeamDeleted::class,
    ];

    public static function booted(): void
    {
        static::updated(function (Team $team) {
            if ($team->isDirty(self::FIELD_BASELINE_EVALUATION_ID)) {
                BaselineEvaluationChangedEvent::dispatch($team->id, $team->baseline_evaluation_id);
            }
        });
    }

    public function endpoints(): HasMany
    {
        return $this->hasMany(SearchEndpoint::class)
            ->latest(SearchEndpoint::FIELD_ID);
    }

    public function models(): HasMany
    {
        return $this->hasMany(SearchModel::class)
            ->latest(SearchModel::FIELD_ID);
    }

    public function teamInvitations(): HasMany
    {
        return $this->hasMany(Jetstream::teamInvitationModel())
            ->oldest();
    }

    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class)
            ->orderBy(Tag::FIELD_ID);
    }

    public function baseline(): HasOne
    {
        return $this->hasOne(SearchEvaluation::class, SearchEvaluation::FIELD_ID, self::FIELD_BASELINE_EVALUATION_ID);
    }
}
