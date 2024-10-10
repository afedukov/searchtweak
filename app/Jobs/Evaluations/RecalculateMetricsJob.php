<?php

namespace App\Jobs\Evaluations;

use App\Actions\Evaluations\RecalculateMetrics;
use App\Models\EvaluationKeyword;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RecalculateMetricsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly int $keywordId)
    {
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return $this->keywordId;
    }

    /**
     * @param RecalculateMetrics $action
     *
     * @return void
     */
    public function handle(RecalculateMetrics $action): void
    {
        $keyword = EvaluationKeyword::find($this->keywordId);
        if ($keyword === null) {
            return;
        }

        try {
            $action->recalculate($keyword);
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [sprintf('keyword:%d', $this->keywordId)];
    }
}
