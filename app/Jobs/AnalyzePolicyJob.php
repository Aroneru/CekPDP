<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\AnalysisError;
use App\Services\AnalysisRunner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\TimeoutExceededException;
use Throwable;

/**
 * Analyses one policy in the background.
 *
 * The job carries only the analysis ID, so policy text never appears in the
 * queue payload or in failed_jobs. It is not retried automatically; the
 * provider call is retried once inside the pipeline instead.
 */
final class AnalyzePolicyJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 600;

    public bool $failOnTimeout = true;

    public function __construct(
        public readonly string $analysisId,
    ) {}

    public function handle(AnalysisRunner $runner): void
    {
        $runner->run($this->analysisId);
    }

    public function failed(?Throwable $exception): void
    {
        app(AnalysisRunner::class)->fail(
            $this->analysisId,
            $exception instanceof TimeoutExceededException ? AnalysisError::Timeout : AnalysisError::Internal,
        );
    }
}
