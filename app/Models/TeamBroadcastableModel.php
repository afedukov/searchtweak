<?php

namespace App\Models;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Database\Eloquent\BroadcastsEvents;
use Illuminate\Database\Eloquent\Model;

abstract class TeamBroadcastableModel extends Model
{
    use BroadcastsEvents;

    abstract protected function getBroadcastChannelName(): string;

    public static function bootBroadcastsEvents(): void
    {
        static::created(function (self $model) {
            $model->broadcastCreated([
                new PrivateChannel($model->getBroadcastChannelName()),
            ]);
        });

        static::updated(function (self $model) {
            $model->broadcastUpdated([
                new PrivateChannel($model->getBroadcastChannelName()),
            ]);
        });

        static::deleted(function (self $model) {
            $model->broadcastDeleted([
                new PrivateChannel($model->getBroadcastChannelName()),
            ]);
        });
    }

    /**
     * Get the data to broadcast for the model.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(string $event): array
    {
        return ['model' => $this->withoutRelations()->toArray()];
    }
}
