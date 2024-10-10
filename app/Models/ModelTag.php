<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $model_id
 * @property int $tag_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property SearchModel $model
 * @property Tag $tag
 */
class ModelTag extends Model
{
    public const string FIELD_ID = 'id';
    public const string FIELD_MODEL_ID = 'model_id';
    public const string FIELD_TAG_ID = 'tag_id';
    public const string FIELD_CREATED_AT = 'created_at';
    public const string FIELD_UPDATED_AT = 'updated_at';

    protected $fillable = [
        self::FIELD_MODEL_ID,
        self::FIELD_TAG_ID,
    ];

    protected $casts = [
        self::FIELD_MODEL_ID => 'int',
        self::FIELD_TAG_ID => 'int',
    ];

    public function model(): BelongsTo
    {
        return $this->belongsTo(SearchModel::class, self::FIELD_MODEL_ID);
    }

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }
}
