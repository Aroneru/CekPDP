<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Error codes stored on a failed analysis. They map to friendly, translated
 * messages and never contain policy text.
 */
enum AnalysisError: string
{
    case InvalidProviderOutput = 'invalid_provider_output';
    case RulesetUnavailable = 'ruleset_unavailable';
    case Timeout = 'timeout';
    case Internal = 'internal_error';
}
