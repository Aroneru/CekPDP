<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Pipeline stages, in order. Used for the progress indicator.
 */
enum AnalysisStage: string
{
    case Queued = 'queued';
    case Extracting = 'extracting';
    case Structuring = 'structuring';
    case Evaluating = 'evaluating';
    case Verifying = 'verifying';
    case Scoring = 'scoring';
    case Done = 'done';
}
