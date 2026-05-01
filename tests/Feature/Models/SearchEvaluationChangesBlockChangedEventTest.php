<?php

namespace Tests\Feature\Models;

use App\Events\EvaluationChangesBlockChangedEvent;
use App\Models\SearchEvaluation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class SearchEvaluationChangesBlockChangedEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_changes_block_changed_event_is_dispatched_when_changes_are_blocked(): void
    {
        Event::fake([EvaluationChangesBlockChangedEvent::class]);

        $evaluation = SearchEvaluation::factory()->create();

        $evaluation->blockChanges();

        Event::assertDispatched(
            EvaluationChangesBlockChangedEvent::class,
            fn (EvaluationChangesBlockChangedEvent $event) => $event->broadcastWith() === [
                'id' => $evaluation->id,
                'blocked' => true,
            ],
        );
    }

    public function test_changes_block_changed_event_is_dispatched_when_changes_are_allowed(): void
    {
        $evaluation = SearchEvaluation::factory()->create();
        $evaluation->blockChanges();

        Event::fake([EvaluationChangesBlockChangedEvent::class]);

        $evaluation->allowChanges();

        Event::assertDispatched(
            EvaluationChangesBlockChangedEvent::class,
            fn (EvaluationChangesBlockChangedEvent $event) => $event->broadcastWith() === [
                'id' => $evaluation->id,
                'blocked' => false,
            ],
        );
    }
}
