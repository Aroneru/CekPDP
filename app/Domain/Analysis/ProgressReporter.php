<?php

declare(strict_types=1);

namespace App\Domain\Analysis;

use App\Enums\AnalysisStage;

/**
 * Receives progress updates while an analysis runs (0–100).
 */
interface ProgressReporter
{
    public function report(AnalysisStage $stage, int $percent): void;
}
