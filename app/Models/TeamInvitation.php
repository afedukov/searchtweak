<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Jetstream\Jetstream;
use Laravel\Jetstream\Role;
use Laravel\Jetstream\TeamInvitation as JetstreamTeamInvitation;

/**
 * @property int $id
 * @property int $team_id
 * @property string $email
 * @property string $role
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property Team $team
 */
class TeamInvitation extends JetstreamTeamInvitation
{
    public const string FIELD_ID = 'id';
    public const string FIELD_TEAM_ID = 'team_id';
    public const string FIELD_EMAIL = 'email';
    public const string FIELD_ROLE = 'role';
    public const string FIELD_CREATED_AT = 'created_at';
    public const string FIELD_UPDATED_AT = 'updated_at';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        self::FIELD_EMAIL,
        self::FIELD_ROLE,
    ];

    /**
     * Get the team that the invitation belongs to.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Jetstream::teamModel());
    }

    public function getRole(): Role
    {
        return Jetstream::findRole($this->role);
    }
}
