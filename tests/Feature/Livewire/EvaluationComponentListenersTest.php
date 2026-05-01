<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Evaluations\EvaluationControl;
use App\Livewire\Evaluations\EvaluationFinishButton;
use App\Livewire\Evaluations\EvaluationProgress;
use App\Models\SearchEvaluation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Component;
use ReflectionMethod;
use Tests\TestCase;

class EvaluationComponentListenersTest extends TestCase
{
    use RefreshDatabase;

    public function test_control_listens_to_targeted_events_instead_of_model_updates(): void
    {
        $evaluation = SearchEvaluation::factory()->create();
        $listeners = $this->getListeners(new EvaluationControl(), $evaluation);

        $this->assertSame(
            '$refresh',
            $listeners[sprintf('echo-private:search-evaluation.%s,.evaluation.status.changed', $evaluation->id)] ?? null,
        );
        $this->assertSame(
            '$refresh',
            $listeners[sprintf('echo-private:search-evaluation.%s,.evaluation.keyword-counts.changed', $evaluation->id)] ?? null,
        );
        $this->assertSame(
            '$refresh',
            $listeners[sprintf('echo-private:search-evaluation.%s,.evaluation.changes-block.changed', $evaluation->id)] ?? null,
        );
        $this->assertNotContains(
            sprintf('echo-private:search-evaluation.%s,.SearchEvaluationUpdated', $evaluation->id),
            array_keys($listeners),
        );
    }

    public function test_finish_button_and_progress_do_not_listen_to_model_updates(): void
    {
        $evaluation = SearchEvaluation::factory()->create();
        $broadListener = sprintf('echo-private:search-evaluation.%s,.SearchEvaluationUpdated', $evaluation->id);

        $finishButtonListeners = $this->getListeners(new EvaluationFinishButton(), $evaluation);
        $progressListeners = $this->getListeners(new EvaluationProgress(), $evaluation);

        $this->assertNotContains($broadListener, array_keys($finishButtonListeners));
        $this->assertNotContains($broadListener, array_keys($progressListeners));
        $this->assertSame(
            '$refresh',
            $finishButtonListeners[sprintf('echo-private:search-evaluation.%s,.evaluation.progress.changed', $evaluation->id)] ?? null,
        );
        $this->assertSame(
            '$refresh',
            $progressListeners[sprintf('echo-private:search-evaluation.%s,.evaluation.progress.changed', $evaluation->id)] ?? null,
        );
    }

    /**
     * @return array<string, string>
     */
    private function getListeners(Component $component, SearchEvaluation $evaluation): array
    {
        $component->evaluation = $evaluation;

        $method = new ReflectionMethod($component, 'getListeners');
        $method->setAccessible(true);

        return $method->invoke($component);
    }
}
