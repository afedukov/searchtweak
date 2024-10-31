<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Database\Eloquent\BroadcastsEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $evaluation_metric_id
 * @property float $value
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property EvaluationMetric $metric
 */
class MetricValue extends Model
{
    use BroadcastsEvents;

    public const string FIELD_ID = 'id';
    public const string FIELD_EVALUATION_METRIC_ID = 'evaluation_metric_id';
    public const string FIELD_VALUE = 'value';
    public const string FIELD_CREATED_AT = 'created_at';
    public const string FIELD_UPDATED_AT = 'updated_at';

    protected $table = 'metric_values';

    protected $fillable = [
        self::FIELD_EVALUATION_METRIC_ID,
        self::FIELD_VALUE,
    ];

    protected $casts = [
        self::FIELD_EVALUATION_METRIC_ID => 'int',
        self::FIELD_VALUE => 'float',
    ];

    public static function bootBroadcastsEvents(): void
    {
        static::created(function (self $model) {
            $model->broadcastCreated([
                new PrivateChannel($model->getBroadcastChannelName()),
            ]);
        });
    }

    protected function getBroadcastChannelName(): string
    {
        return sprintf('metric-value.%d', $this->metric->id);
    }

    /**
     * Get the data to broadcast for the model.
     *
     * @return array<string, mixed>
     * @throws \Throwable
     */
    public function broadcastWith(string $event): array
    {
        $change = $this->metric->getChange(
            $this->metric->evaluation->model->team->baseline
        );

        return match ($event) {
            'created' => [
                'metric_id' => $this->metric->id,
                'value' => $this->metric->value,
                'values' => $this->metric->getLastValues(),
                'previous_value' => $this->metric->previous_value,
                'changeHTML' => view('components.metrics.metric-change', ['change' => $change])
                    ->render(),
            ],
            default => [],
        };
    }

    public function metric(): BelongsTo
    {
        return $this->belongsTo(EvaluationMetric::class, self::FIELD_EVALUATION_METRIC_ID);
    }
}
