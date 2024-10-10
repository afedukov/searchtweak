<?php

namespace App\Jobs\Evaluations;

use App\Models\EvaluationKeyword;
use App\Models\SearchSnapshot;
use App\Services\Models\ExecuteModelService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessKeywordJob implements ShouldQueue, ShouldBeUnique
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const int RETRY_ATTEMPTS = 2;

    public int $tries = self::RETRY_ATTEMPTS + 1;

    private ExecuteModelService $executeModelService;

    /**
     * Create a new job instance.
     */
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
     * Execute the job.
     */
    public function handle(ExecuteModelService $executeModelService): void
    {
        $keyword = EvaluationKeyword::find($this->keywordId);
        if ($keyword === null) {
            return;
        }

        if ($keyword->execution_code !== null) {
            return;
        }

        if ($this->attempts() > self::RETRY_ATTEMPTS) {
            $keyword->execution_code = 500;
            $keyword->execution_message = 'Failed to execute keyword';
            $keyword->failed = true;
            $keyword->save();

            return;
        }

        $this->executeModelService = $executeModelService
            ->initialize($keyword->evaluation->model)
            ->setLimit($keyword->evaluation->getNumResults());

        $this->executeKeyword($keyword);
    }

    private function executeKeyword(EvaluationKeyword $keyword): void
    {
        $result = $this->executeModelService->execute($keyword->keyword);

        $snapshots = [];
        foreach ($result->getDocuments() as $doc) {
            $snapshots[] = SearchSnapshot::createFromDocument($doc)->toArray();
        }

        $keyword->snapshots()->createMany($snapshots);

        $keyword->total_count = $result->isSuccessful() ? $result->getTotalCount() : null;
        $keyword->execution_code = $result->getCode();
        $keyword->execution_message = substr($result->getMessage(), 0, 512);
        $keyword->failed = $keyword->isFailed();
        $keyword->save();
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
