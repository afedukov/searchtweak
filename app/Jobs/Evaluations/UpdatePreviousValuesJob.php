<?php

namespace App\Jobs\Evaluations;

use App\Models\SearchModel;
use App\Services\Metrics\PreviousEvaluationMetricService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdatePreviousValuesJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly int $modelId, private readonly int $evaluationId)
    {
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return sprintf('%d-%d', $this->modelId, $this->evaluationId);
    }

    /**
     * @param PreviousEvaluationMetricService $service
     *
     * @return void
     */
    public function handle(PreviousEvaluationMetricService $service): void
    {
        $model = SearchModel::find($this->modelId);
        if ($model === null) {
            return;
        }

        try {
            $service->updatePreviousValues($model->id, $this->evaluationId);
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
        return [
            'previous-values',
            sprintf('model:%d', $this->modelId),
            sprintf('evaluation:%d', $this->evaluationId)
        ];
    }
}
