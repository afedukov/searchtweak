<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $notification_class
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class NotificationUnsubscription extends Model
{
    public const string FIELD_ID = 'id';
    public const string FIELD_USER_ID = 'user_id';
    public const string FIELD_NOTIFICATION_CLASS = 'notification_class';
    public const string FIELD_CREATED_AT = 'created_at';
    public const string FIELD_UPDATED_AT = 'updated_at';

    protected $fillable = [
        self::FIELD_USER_ID,
        self::FIELD_NOTIFICATION_CLASS,
    ];

    protected $casts = [
        self::FIELD_USER_ID => 'int',
    ];
}
