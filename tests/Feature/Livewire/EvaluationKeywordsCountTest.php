<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Evaluations\EvaluationKeywordsCount;
use App\Livewire\Evaluations\EvaluationStatus;
use App\Models\SearchEvaluation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

class EvaluationKeywordsCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_counts_are_reloaded_when_component_refreshes(): void
    {
        $evaluation = SearchEvaluation::factory()->create([
            SearchEvaluation::FIELD_SUCCESSFUL_KEYWORDS => 1,
            SearchEvaluation::FIELD_FAILED_KEYWORDS => 2,
        ]);

        $component = Livewire::test(EvaluationKeywordsCount::class, ['evaluation' => $evaluation])
            ->assertSee('1')
            ->assertSee('2');

        $evaluation->update([
            SearchEvaluation::FIELD_SUCCESSFUL_KEYWORDS => 3,
            SearchEvaluation::FIELD_FAILED_KEYWORDS => 4,
        ]);

        $component
            ->call('$refresh')
            ->assertSee('3')
            ->assertSee('4');
    }

    public function test_counts_and_status_components_listen_to_keyword_counts_event(): void
    {
        $evaluation = SearchEvaluation::factory()->create();
        $expectedKey = sprintf(
            'echo-private:search-evaluation.%s,.evaluation.keyword-counts.changed',
            $evaluation->id,
        );

        $keywordsCountListeners = $this->getComponentListeners(new EvaluationKeywordsCount(), $evaluation);
        $statusListeners = $this->getComponentListeners(new EvaluationStatus(), $evaluation);

        $this->assertSame('$refresh', $keywordsCountListeners[$expectedKey] ?? null);
        $this->assertSame('$refresh', $statusListeners[$expectedKey] ?? null);
        $this->assertNotContains(
            sprintf('echo-private:search-evaluation.%s,.SearchEvaluationUpdated', $evaluation->id),
            array_keys($keywordsCountListeners),
        );
        $this->assertNotContains(
            sprintf('echo-private:search-evaluation.%s,.SearchEvaluationUpdated', $evaluation->id),
            array_keys($statusListeners),
        );
    }

    /**
     * @return array<string, string>
     */
    private function getComponentListeners(EvaluationKeywordsCount|EvaluationStatus $component, SearchEvaluation $evaluation): array
    {
        $component->evaluation = $evaluation;

        $method = new ReflectionMethod($component, 'getListeners');
        $method->setAccessible(true);

        return $method->invoke($component);
    }
}
