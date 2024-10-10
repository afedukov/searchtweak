<?php

namespace App\Jobs\Evaluations;

use App\Actions\Evaluations\StartSearchEvaluation;
use App\Models\SearchEvaluation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class StartEvaluationJob implements ShouldQueue, ShouldBeUnique
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
    public function handle(StartSearchEvaluation $action): void
    {
        $evaluation = SearchEvaluation::find($this->evaluationId);
        if ($evaluation === null) {
            return;
        }

        try {
            $action->start($evaluation);
        } catch (\Throwable $e) {
            Log::error($e->getMessage());

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
