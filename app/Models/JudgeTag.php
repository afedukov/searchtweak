<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $judge_id
 * @property int $tag_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property Judge $judge
 * @property Tag $tag
 */
class JudgeTag extends Model
{
    public const string FIELD_ID = 'id';
    public const string FIELD_JUDGE_ID = 'judge_id';
    public const string FIELD_TAG_ID = 'tag_id';
    public const string FIELD_CREATED_AT = 'created_at';
    public const string FIELD_UPDATED_AT = 'updated_at';

    protected $fillable = [
        self::FIELD_JUDGE_ID,
        self::FIELD_TAG_ID,
    ];

    protected $casts = [
        self::FIELD_JUDGE_ID => 'int',
        self::FIELD_TAG_ID => 'int',
    ];

    public function judge(): BelongsTo
    {
        return $this->belongsTo(Judge::class, self::FIELD_JUDGE_ID);
    }

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }
}
