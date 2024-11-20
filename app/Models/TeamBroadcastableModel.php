<?php

namespace App\Models;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Database\Eloquent\BroadcastsEvents;
use Illuminate\Database\Eloquent\Model;

abstract class TeamBroadcastableModel extends Model
{
    use BroadcastsEvents;

    abstract protected function getBroadcastChannelName(): string;

    /**
     * Get additional channels to broadcast to when the model is created.
     *
     * @return array<\Illuminate\Broadcasting\Channel>
     */
    protected function additionalBroadcastCreatedChannels(): array
    {
        return [];
    }

    /**
     * Get additional channels to broadcast to when the model is updated.
     *
     * @return array<\Illuminate\Broadcasting\Channel>
     */
    protected function additionalBroadcastUpdatedChannels(): array
    {
        return [];
    }

    /**
     * Get additional channels to broadcast to when the model is deleted.
     *
     * @return array<\Illuminate\Broadcasting\Channel>
     */
    protected function additionalBroadcastDeletedChannels(): array
    {
        return [];
    }

    public static function bootBroadcastsEvents(): void
    {
        static::created(function (self $model) {
            $model->broadcastCreated(array_merge([
                new PrivateChannel($model->getBroadcastChannelName()),
            ], $model->additionalBroadcastCreatedChannels()));
        });

        static::updated(function (self $model) {
            $model->broadcastUpdated(array_merge(
                [new PrivateChannel($model->getBroadcastChannelName()),
            ], $model->additionalBroadcastUpdatedChannels()));
        });

        static::deleted(function (self $model) {
            $model->broadcastDeleted(array_merge([
                new PrivateChannel($model->getBroadcastChannelName()),
            ], $model->additionalBroadcastDeletedChannels()));
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
