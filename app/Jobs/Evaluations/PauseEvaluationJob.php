<?php

namespace App\Jobs\Evaluations;

use App\Actions\Evaluations\PauseSearchEvaluation;
use App\Models\SearchEvaluation;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PauseEvaluationJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly int $evaluationId)
    {
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return $this->evaluationId;
    }

    /**
     * Execute the job.
     */
    public function handle(PauseSearchEvaluation $action): void
    {
        $evaluation = SearchEvaluation::find($this->evaluationId);
        if ($evaluation === null) {
            return;
        }

        try {
            $action->pause($evaluation);
        } catch (Exception $e) {
            Log::error($e->getMessage());
        } finally {
            $evaluation->allowChanges();
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [sprintf('evaluation:%d', $this->evaluationId)];
    }
}
