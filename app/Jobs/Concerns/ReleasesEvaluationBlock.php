<?php

namespace App\Jobs\Concerns;

use App\Models\SearchEvaluation;

/**
 * Releases the SearchEvaluation `changes_blocked` flag when a job fails permanently.
 *
 * The flag is normally cleared in the job's `finally` block, but PHP `finally`
 * does not run when the worker is killed by SIGTERM (e.g. on timeout). Laravel
 * still calls `failed()` after the final retry, so this trait keeps the UI
 * spinner from being stuck until the cache TTL (15 min) elapses.
 *
 * Host class must expose `$this->evaluationId`.
 */
trait ReleasesEvaluationBlock
{
    public function failed(\Throwable $e): void
    {
        SearchEvaluation::find($this->evaluationId)?->allowChangesAndNotify();
    }
}
