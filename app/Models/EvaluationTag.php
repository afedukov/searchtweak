<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $evaluation_id
 * @property int $tag_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property SearchEvaluation $evaluation
 * @property Tag $tag
 */
class EvaluationTag extends Model
{
    public const string FIELD_ID = 'id';
    public const string FIELD_EVALUATION_ID = 'evaluation_id';
    public const string FIELD_TAG_ID = 'tag_id';
    public const string FIELD_CREATED_AT = 'created_at';
    public const string FIELD_UPDATED_AT = 'updated_at';

    protected $fillable = [
        self::FIELD_EVALUATION_ID,
        self::FIELD_TAG_ID,
    ];

    protected $casts = [
        self::FIELD_EVALUATION_ID => 'int',
        self::FIELD_TAG_ID => 'int',
    ];

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(SearchEvaluation::class, self::FIELD_EVALUATION_ID);
    }

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }
}
