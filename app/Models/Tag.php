<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $team_id
 * @property string $color
 * @property string $name
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property Team $team
 */
class Tag extends Model
{
    public const string FIELD_ID = 'id';
    public const string FIELD_TEAM_ID = 'team_id';
    public const string FIELD_COLOR = 'color';
    public const string FIELD_NAME = 'name';
    public const string FIELD_CREATED_AT = 'created_at';
    public const string FIELD_UPDATED_AT = 'updated_at';

    protected $fillable = [
        self::FIELD_TEAM_ID,
        self::FIELD_COLOR,
        self::FIELD_NAME,
    ];

    protected $casts = [
        self::FIELD_TEAM_ID => 'int',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public static function getColors(): array
    {
        return [
            'blue' => 'bg-blue-200 text-blue-800 dark:bg-blue-800 dark:text-blue-300',
            'indigo' => 'bg-indigo-200 text-indigo-800 dark:bg-indigo-800 dark:text-indigo-300',
            'green' => 'bg-green-200 text-green-800 dark:bg-green-800 dark:text-green-300',
            'red' => 'bg-red-200 text-red-800 dark:bg-red-800 dark:text-red-300',
            'yellow' => 'bg-yellow-200 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-300',
            'purple' => 'bg-purple-200 text-purple-800 dark:bg-purple-800 dark:text-purple-300',
            'pink' => 'bg-pink-200 text-pink-800 dark:bg-pink-800 dark:text-pink-300',
            'orange' => 'bg-orange-200 text-orange-800 dark:bg-orange-800 dark:text-orange-300',
            'gray' => 'bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
            'cyan' => 'bg-cyan-200 text-cyan-800 dark:bg-cyan-800 dark:text-cyan-300',
            'teal' => 'bg-teal-200 text-teal-800 dark:bg-teal-800 dark:text-teal-300',
            'lime' => 'bg-lime-200 text-lime-800 dark:bg-lime-800 dark:text-lime-300',
            'emerald' => 'bg-emerald-200 text-emerald-800 dark:bg-emerald-800 dark:text-emerald-300',
            'rose' => 'bg-rose-200 text-rose-800 dark:bg-rose-800 dark:text-rose-300',
            'sky' => 'bg-sky-200 text-sky-800 dark:bg-sky-800 dark:text-sky-300',
            'slate' => 'bg-slate-500 text-slate-200 dark:bg-slate-500 dark:text-slate-300',
        ];
    }

    public function getColorClass(): string
    {
        return self::getColors()[$this->color] ?? self::getColors()['gray'];
    }

    public function toArray(): array
    {
        return parent::toArray() + [
            'color_class' => $this->getColorClass(),
        ];
    }
}
