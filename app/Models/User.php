<?php

namespace App\Models;

use App\Http\Middleware\UserOnline;
use App\Notifications\ResetPassword;
use App\Policies\Roles;
use App\Services\Evaluations\UserFeedbackService;
use App\Services\WidgetsService;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property bool $super_admin
 * @property string $name
 * @property string $email
 * @property Carbon $email_verified_at
 * @property string $password
 * @property string $two_factor_secret
 * @property string $two_factor_recovery_codes
 * @property Carbon $two_factor_confirmed_at
 * @property string $remember_token
 * @property int $current_team_id
 * @property string $profile_photo_path
 * @property bool $newsletter
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read string $profile_photo_url
 *
 * @property Team $currentTeam
 * @property Collection<Team> $ownedTeams
 * @property Collection<Team> $teams
 * @property Collection<UserWidget> $widgets
 * @property Collection<SearchEndpoint> $endpoints
 * @property Collection<SearchModel> $models
 * @property Collection<SearchEvaluation> $evaluations
 * @property Collection<NotificationUnsubscription> $notificationUnsubscriptions
 */
class User extends Authenticatable implements TaggableInterface
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use HasTeams;
    use Notifiable;
    use TwoFactorAuthenticatable;

    public const string FIELD_ID = 'id';
    public const string FIELD_SUPER_ADMIN = 'super_admin';
    public const string FIELD_NAME = 'name';
    public const string FIELD_EMAIL = 'email';
    public const string FIELD_EMAIL_VERIFIED_AT = 'email_verified_at';
    public const string FIELD_PASSWORD = 'password';
    public const string FIELD_TWO_FACTOR_SECRET = 'two_factor_secret';
    public const string FIELD_TWO_FACTOR_RECOVERY_CODES = 'two_factor_recovery_codes';
    public const string FIELD_TWO_FACTOR_CONFIRMED_AT = 'two_factor_confirmed_at';
    public const string FIELD_REMEMBER_TOKEN = 'remember_token';
    public const string FIELD_CURRENT_TEAM_ID = 'current_team_id';
    public const string FIELD_PROFILE_PHOTO_PATH = 'profile_photo_path';
    public const string FIELD_NEWSLETTER = 'newsletter';
    public const string FIELD_CREATED_AT = 'created_at';
    public const string FIELD_UPDATED_AT = 'updated_at';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        self::FIELD_NAME,
        self::FIELD_EMAIL,
        self::FIELD_PASSWORD,
        self::FIELD_NEWSLETTER,
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        self::FIELD_PASSWORD,
        self::FIELD_REMEMBER_TOKEN,
        self::FIELD_TWO_FACTOR_SECRET,
        self::FIELD_TWO_FACTOR_RECOVERY_CODES,
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        self::FIELD_SUPER_ADMIN => 'bool',
        self::FIELD_EMAIL_VERIFIED_AT => 'datetime',
        self::FIELD_NEWSLETTER => 'bool',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    public function isAdmin(Team $team): bool
    {
        return $this->belongsToTeam($team) && $this->teamRole($team)->key === Roles::ROLE_ADMIN['key'];
    }

    public function isEvaluator(Team $team): bool
    {
        return $this->belongsToTeam($team) && $this->teamRole($team)->key === Roles::ROLE_EVALUATOR['key'];
    }

    public function isOwner(Team $team): bool
    {
        return $this->id === $team->user_id;
    }

    public function widgets(): HasMany
    {
        return $this->hasMany(UserWidget::class)
            ->where(UserWidget::FIELD_TEAM_ID, $this->current_team_id)
            ->orderBy(UserWidget::FIELD_POSITION)
            ->orderByDesc(UserWidget::FIELD_CREATED_AT);
    }

    public function syncWidgets(array $widgets): void
    {
        $this->widgets()->delete();
        $this->widgets()->createMany($widgets);
    }

    public function endpoints(): HasMany
    {
        return $this->hasMany(SearchEndpoint::class);
    }

    public function models(): HasMany
    {
        return $this->hasMany(SearchModel::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(SearchEvaluation::class);
    }

    public function isOnline(): bool
    {
        return Cache::has(UserOnline::getCacheKey($this->id));
    }

    public function attachWidget(string $widgetClass, array $settings = []): UserWidget
    {
        return app(WidgetsService::class)->attachWidget($this, $widgetClass, $settings);
    }

    public function canCurrentTeam(string $permission): bool
    {
        return $this->hasTeamPermission($this->currentTeam, $permission);
    }

    public function getUngradedSnapshotsCount(): int
    {
        return app(UserFeedbackService::class)->getUngradedSnapshotsCountCached($this);
    }

    /**
     * All the tags that were assigned to the user.
     *
     * ATTENTION: across all teams.
     *
     * @return BelongsToMany
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, UserTag::class, UserTag::FIELD_USER_ID, UserTag::FIELD_TAG_ID, self::FIELD_ID, Tag::FIELD_ID)
            ->withTimestamps()
            ->orderBy(Tag::FIELD_ID);
    }

    /**
     * @param int $teamId
     *
     * @return Collection<Tag>

     */
    public function getTeamTags(int $teamId): Collection
    {
        return $this->tags->where(Tag::FIELD_TEAM_ID, $teamId)->values();
    }

    /**
     * Send a password reset notification to the user.
     *
     * @param  string  $token
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPassword($token));
    }

    public function notificationUnsubscriptions(): HasMany
    {
        return $this->hasMany(NotificationUnsubscription::class);
    }

    public function updateNotificationSubscription(string $notificationClass, bool $subscribe): void
    {
        $isSubscribed = $this->isSubscribedToNotification($notificationClass);

        if ($subscribe && !$isSubscribed) {
            $this->subscribeToNotification($notificationClass);
        } elseif (!$subscribe && $isSubscribed) {
            $this->unsubscribeFromNotification($notificationClass);
        }
    }

    public function subscribeToNotification(string $notificationClass): void
    {
        $this->notificationUnsubscriptions()
            ->where(NotificationUnsubscription::FIELD_NOTIFICATION_CLASS, $notificationClass)
            ->delete();
    }

    public function unsubscribeFromNotification(string $notificationClass): void
    {
        $this->notificationUnsubscriptions()->create([
            NotificationUnsubscription::FIELD_NOTIFICATION_CLASS => $notificationClass,
        ]);
    }

    public function isSubscribedToNotification(string $notificationClass): bool
    {
        return $this->notificationUnsubscriptions()
            ->where(NotificationUnsubscription::FIELD_NOTIFICATION_CLASS, $notificationClass)
            ->doesntExist();
    }
}
