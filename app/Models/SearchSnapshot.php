<?php

namespace App\Models;

use App\Services\Mapper\Document;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $evaluation_keyword_id
 * @property int $position
 * @property string $doc_id
 * @property string $image
 * @property string $name
 * @property array $doc
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property EvaluationKeyword $keyword
 * @property Collection<UserFeedback> $feedbacks
 */
class SearchSnapshot extends Model
{
    public const string FIELD_ID = 'id';
    public const string FIELD_EVALUATION_KEYWORD_ID = 'evaluation_keyword_id';
    public const string FIELD_POSITION = 'position';
    public const string FIELD_DOC_ID = 'doc_id';
    public const string FIELD_IMAGE = 'image';
    public const string FIELD_NAME = 'name';
    public const string FIELD_DOC = 'doc';
    public const string FIELD_CREATED_AT = 'created_at';
    public const string FIELD_UPDATED_AT = 'updated_at';

    protected $fillable = [
        self::FIELD_EVALUATION_KEYWORD_ID,
        self::FIELD_POSITION,
        self::FIELD_DOC_ID,
        self::FIELD_IMAGE,
        self::FIELD_NAME,
        self::FIELD_DOC,
    ];

    protected $casts = [
        self::FIELD_EVALUATION_KEYWORD_ID => 'int',
        self::FIELD_POSITION => 'int',
        self::FIELD_DOC => 'array',
    ];

    public static function booted(): void
    {
        static::created(function (SearchSnapshot $snapshot) {
            $snapshot->createUserFeedbacks(
                $snapshot->keyword->evaluation->getFeedbackStrategy()
            );
        });
    }

    protected function createUserFeedbacks(int $count = 1): void
    {
        $this->feedbacks()->createMany(
            array_fill(0, $count, [])
        );
    }

    public function keyword(): BelongsTo
    {
        return $this->belongsTo(EvaluationKeyword::class, self::FIELD_EVALUATION_KEYWORD_ID);
    }

    public function feedbacks(): HasMany
    {
        return $this->hasMany(UserFeedback::class, UserFeedback::FIELD_SEARCH_SNAPSHOT_ID);
    }

    /**
     * Determine if the snapshot has feedback from the given user.
     *
     * @param int $userId
     *
     * @return bool
     */
    public function hasFeedback(int $userId): bool
    {
        return $this->feedbacks
            ->filter(fn (UserFeedback $feedback) =>
                $feedback->grade !== null && $feedback->user_id === $userId
            )
            ->count() > 0;
    }

    /**
     * Determine if the snapshot needs feedback.
     *
     * @return bool
     */
    public function needsFeedback(): bool
    {
        return $this->feedbacks
            ->whereNull(UserFeedback::FIELD_GRADE)
            ->count() > 0;
    }

    public static function createFromDocument(Document $doc): SearchSnapshot
    {
        return new SearchSnapshot([
            SearchSnapshot::FIELD_POSITION => $doc->getPosition(),
            SearchSnapshot::FIELD_DOC_ID => $doc->getId(),
            SearchSnapshot::FIELD_IMAGE => $doc->getImage(),
            SearchSnapshot::FIELD_NAME => $doc->getName(),
            SearchSnapshot::FIELD_DOC => $doc->getAttributes(),
        ]);
    }
}
