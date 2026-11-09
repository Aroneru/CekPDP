<?php

declare(strict_types=1);

namespace App\Enums;

enum RuleEvaluator: string
{
    /** Evaluated by the configured AI provider (or the mock provider). */
    case Ai = 'ai';

    /** Decided first by deterministic language detection; the AI is only asked for English-only policies. */
    case LanguageCheck = 'language_check';
}
