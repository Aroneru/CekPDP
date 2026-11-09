<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Analysis\ProgressReporter;
use App\Enums\AnalysisStage;
use App\Models\Analysis;

/**
 * Writes pipeline progress to the analysis row, where the progress page polls
 * for it.
 */
final class StoredProgress implements ProgressReporter
{
    public function __construct(
        private readonly string $analysisId,
    ) {}

    public function report(AnalysisStage $stage, int $percent): void
    {
        Analysis::query()->whereKey($this->analysisId)->update([
            'stage' => $stage->value,
            'progress' => max(0, min(99, $percent)),
        ]);
    }
}
