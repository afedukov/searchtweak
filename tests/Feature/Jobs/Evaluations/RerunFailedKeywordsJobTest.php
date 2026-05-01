<?php

namespace Tests\Feature\Jobs\Evaluations;

use App\Actions\Evaluations\RerunFailedEvaluationKeywords;
use App\Events\EvaluationChangesBlockChangedEvent;
use App\Jobs\Evaluations\RerunFailedKeywordsJob;
use App\Models\EvaluationKeyword;
use App\Models\SearchEvaluation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RerunFailedKeywordsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_failure_releases_changes_block_without_retry(): void
    {
        $evaluation = SearchEvaluation::factory()->create();
        $evaluation->blockChanges();

        (new RerunFailedKeywordsJob($evaluation->id))->handle(app(RerunFailedEvaluationKeywords::class));

        $evaluation->refresh();

        $this->assertFalse($evaluation->changes_blocked);
    }

    public function test_unexpected_failure_is_rethrown_for_retry_and_releases_changes_block(): void
    {
        $evaluation = SearchEvaluation::factory()->active()->create();
        EvaluationKeyword::factory()->failed()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
        ]);
        $evaluation->blockChanges();

        Event::fake([EvaluationChangesBlockChangedEvent::class]);

        Bus::shouldReceive('batch')
            ->once()
            ->andThrow(new \RuntimeException('dispatch failed'));

        try {
            SearchEvaluation::withoutBroadcasting(
                fn () => (new RerunFailedKeywordsJob($evaluation->id))->handle(app(RerunFailedEvaluationKeywords::class)),
            );
            $this->fail('Expected RuntimeException to be re-thrown for retry.');
        } catch (\RuntimeException $e) {
            $this->assertSame('dispatch failed', $e->getMessage());
        }

        $evaluation->refresh();
        $keyword = $evaluation->keywords()->first();

        $this->assertFalse($evaluation->changes_blocked);
        $this->assertSame(500, $keyword->execution_code);
        $this->assertTrue($keyword->failed);
    }
}
