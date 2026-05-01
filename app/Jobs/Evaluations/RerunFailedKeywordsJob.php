<?php

namespace App\Jobs\Evaluations;

use App\Actions\Evaluations\RerunFailedEvaluationKeywords;
use App\Exceptions\CannotRerunFailedKeywordsException;
use App\Jobs\Concerns\ReleasesEvaluationBlock;
use App\Models\SearchEvaluation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RerunFailedKeywordsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, ReleasesEvaluationBlock, SerializesModels;

    public function __construct(private readonly int $evaluationId)
    {
    }

    public function uniqueId(): string
    {
        return (string) $this->evaluationId;
    }

    public function handle(RerunFailedEvaluationKeywords $action): void
    {
        $evaluation = SearchEvaluation::find($this->evaluationId);
        if ($evaluation === null) {
            return;
        }

        try {
            $action->rerun($evaluation);
        } catch (CannotRerunFailedKeywordsException $e) {
            Log::warning($e->getMessage());

            $evaluation->allowChangesAndNotify();
        } catch (\Throwable $e) {
            Log::error($e->getMessage());

            $evaluation->allowChangesAndNotify();

            throw $e;
        }
    }

    /**
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [sprintf('evaluation:%d', $this->evaluationId)];
    }
}
