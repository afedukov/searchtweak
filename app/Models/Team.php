<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property User $owner
 * @property Collection<User> $users
 * @property Collection<SearchEndpoint> $endpoints
 * @property Collection<SearchModel> $models
 * @property Collection<TeamInvitation> $teamInvitations
 * @property Collection<Tag> $tags
 */
class Team extends JetstreamTeam
{
    use HasFactory, HasApiTokens;

    public const string FIELD_ID = 'id';
    public const string FIELD_USER_ID = 'user_id';
    public const string FIELD_NAME = 'name';
    public const string FIELD_PERSONAL_TEAM = 'personal_team';
    public const string FIELD_CREATED_AT = 'created_at';
    public const string FIELD_UPDATED_AT = 'updated_at';

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        self::FIELD_PERSONAL_TEAM => 'boolean',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        self::FIELD_NAME,
        self::FIELD_PERSONAL_TEAM,
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
}
