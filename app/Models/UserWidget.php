<?php

namespace App\Models;

use App\Livewire\Widgets\BaseWidget;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property string $id
 * @property int $user_id
 * @property int $team_id
 * @property string $widget_class
 * @property int $position
 * @property bool $visible
 * @property array|null $settings
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read string $name
 */
class UserWidget extends Model
{
    public const string FIELD_ID = 'id';
    public const string FIELD_USER_ID = 'user_id';
    public const string FIELD_TEAM_ID = 'team_id';
    public const string FIELD_WIDGET_CLASS = 'widget_class';
    public const string FIELD_POSITION = 'position';
    public const string FIELD_VISIBLE = 'visible';
    public const string FIELD_SETTINGS = 'settings';
    public const string FIELD_CREATED_AT = 'created_at';
    public const string FIELD_UPDATED_AT = 'updated_at';

    public const string FIELD_NAME = 'name';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $casts = [
        self::FIELD_USER_ID => 'int',
        self::FIELD_TEAM_ID => 'int',
        self::FIELD_POSITION => 'int',
        self::FIELD_VISIBLE => 'bool',
        self::FIELD_SETTINGS => 'array',
    ];

    protected $fillable = [
        self::FIELD_ID,
        self::FIELD_USER_ID,
        self::FIELD_TEAM_ID,
        self::FIELD_WIDGET_CLASS,
        self::FIELD_POSITION,
        self::FIELD_VISIBLE,
        self::FIELD_SETTINGS,
    ];

    public static function booted(): void
    {
        static::creating(function (UserWidget $userWidget) {
            if (!$userWidget->id) {
                $userWidget->id = Str::uuid()->toString();
            }
        });
    }

    public function getNameAttribute(): string
    {
        /** @var BaseWidget|class-string $widgetClass */
        $widgetClass = $this->widget_class;

        return $widgetClass::getWidgetName($this->settings);
    }

    public function toArray(): array
    {
        return parent::toArray() + [
            self::FIELD_NAME => $this->name,
        ];
    }
}
