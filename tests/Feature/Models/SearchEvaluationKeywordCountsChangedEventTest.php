<?php

namespace Tests\Feature\Models;

use App\Events\EvaluationKeywordCountsChangedEvent;
use App\Models\SearchEvaluation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class SearchEvaluationKeywordCountsChangedEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_keyword_counts_changed_event_is_dispatched_when_processed_keyword_counts_change(): void
    {
        Event::fake([EvaluationKeywordCountsChangedEvent::class]);

        $evaluation = SearchEvaluation::factory()->create([
            SearchEvaluation::FIELD_SUCCESSFUL_KEYWORDS => 1,
            SearchEvaluation::FIELD_FAILED_KEYWORDS => 2,
        ]);

        $evaluation->update([
            SearchEvaluation::FIELD_SUCCESSFUL_KEYWORDS => 3,
        ]);

        Event::assertDispatched(
            EvaluationKeywordCountsChangedEvent::class,
            fn (EvaluationKeywordCountsChangedEvent $event) => $event->broadcastWith() === [
                'id' => $evaluation->id,
                'successful_keywords' => 3,
                'failed_keywords' => 2,
            ],
        );
    }

    public function test_keyword_counts_changed_event_is_not_dispatched_for_unrelated_updates(): void
    {
        Event::fake([EvaluationKeywordCountsChangedEvent::class]);

        $evaluation = SearchEvaluation::factory()->create([
            SearchEvaluation::FIELD_SUCCESSFUL_KEYWORDS => 1,
            SearchEvaluation::FIELD_FAILED_KEYWORDS => 2,
        ]);

        $evaluation->update([
            SearchEvaluation::FIELD_DESCRIPTION => 'Updated description',
        ]);

        Event::assertNotDispatched(EvaluationKeywordCountsChangedEvent::class);
    }
}
