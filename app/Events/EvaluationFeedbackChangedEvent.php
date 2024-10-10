<?php

namespace App\Events;

use App\Models\UserFeedback;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EvaluationFeedbackChangedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(private readonly UserFeedback $feedback)
    {
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(sprintf('search-evaluation.%d', $this->feedback->snapshot->keyword->search_evaluation_id)),
            new PrivateChannel(sprintf('team.%d', $this->feedback->snapshot->keyword->evaluation->model->team_id)),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'evaluation.feedback.changed';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->feedback->snapshot->keyword->search_evaluation_id,
            'feedback' => $this->feedback->withoutRelations()->toArray(),
        ];
    }
}
