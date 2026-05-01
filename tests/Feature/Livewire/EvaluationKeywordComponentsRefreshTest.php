<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Evaluations\EvaluationKeywordCountBadge;
use App\Livewire\Evaluations\EvaluationKeywordMetric;
use App\Livewire\Evaluations\EvaluationKeywordRow;
use App\Models\EvaluationKeyword;
use App\Models\EvaluationMetric;
use App\Models\KeywordMetric;
use App\Models\SearchEvaluation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

class EvaluationKeywordComponentsRefreshTest extends TestCase
{
    use RefreshDatabase;

    public function test_keyword_row_refreshes_keyword_on_keywords_changed_event(): void
    {
        $this->actingAs(User::factory()->create());

        $evaluation = SearchEvaluation::factory()->create();
        $keyword = EvaluationKeyword::factory()->failed()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
        ]);

        $component = Livewire::test(EvaluationKeywordRow::class, [
            'evaluation' => $evaluation,
            'keyword' => $keyword,
        ])->assertSee('Failed');

        $keyword->forceFill([
            EvaluationKeyword::FIELD_FAILED => false,
            EvaluationKeyword::FIELD_EXECUTION_CODE => 200,
        ])->save();

        $component
            ->call('refreshKeyword')
            ->assertDontSee('Failed');
    }

    public function test_keyword_metric_refreshes_keyword_metrics_on_keywords_changed_event(): void
    {
        $evaluation = SearchEvaluation::factory()->create();
        $keyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
        ]);
        $metric = EvaluationMetric::factory()->create([
            EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
            EvaluationMetric::FIELD_SCORER_TYPE => 'precision',
            EvaluationMetric::FIELD_NUM_RESULTS => 10,
        ]);

        $component = Livewire::test(EvaluationKeywordMetric::class, [
            'keyword' => $keyword->load('keywordMetrics'),
            'metric' => $metric,
        ]);

        KeywordMetric::create([
            KeywordMetric::FIELD_EVALUATION_KEYWORD_ID => $keyword->id,
            KeywordMetric::FIELD_EVALUATION_METRIC_ID => $metric->id,
            KeywordMetric::FIELD_VALUE => 0.5,
        ]);

        $component
            ->call('refreshKeywordMetric')
            ->assertSee('50');
    }

    public function test_keyword_components_listen_to_keywords_changed_event(): void
    {
        $evaluation = SearchEvaluation::factory()->create();
        $keyword = EvaluationKeyword::factory()->create([
            EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
        ]);
        $metric = EvaluationMetric::factory()->create([
            EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
        ]);
        $expectedKey = sprintf('echo-private:search-evaluation.%d,.evaluation.keywords.changed', $evaluation->id);

        $rowListeners = $this->getListeners(new EvaluationKeywordRow(), [
            'evaluation' => $evaluation,
            'keyword' => $keyword,
        ]);
        $metricListeners = $this->getListeners(new EvaluationKeywordMetric(), [
            'keyword' => $keyword,
            'metric' => $metric,
        ]);
        $countBadgeListeners = $this->getListeners(new EvaluationKeywordCountBadge(), [
            'keyword' => $keyword,
        ]);

        $this->assertSame('refreshKeyword', $rowListeners[$expectedKey] ?? null);
        $this->assertSame('refreshKeywordMetric', $metricListeners[$expectedKey] ?? null);
        $this->assertSame('refreshKeyword', $countBadgeListeners[$expectedKey] ?? null);
    }

    /**
     * @param array<string, mixed> $properties
     *
     * @return array<string, string>
     */
    private function getListeners(object $component, array $properties): array
    {
        foreach ($properties as $property => $value) {
            $component->{$property} = $value;
        }

        $method = new ReflectionMethod($component, 'getListeners');
        $method->setAccessible(true);

        return $method->invoke($component);
    }
}
