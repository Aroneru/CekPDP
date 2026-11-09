<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Where a rule's result came from.
 */
enum EvaluationSource: string
{
    /** The configured provider (Claude, or the offline mock). */
    case Provider = 'provider';

    /** Deterministic language detection (rule PDP-LANG-01). */
    case LanguageCheck = 'language_check';

    /**
     * Whether an Addressed or Partially addressed result must be backed by a
     * verified quote.
     */
    public function requiresEvidence(): bool
    {
        return $this === self::Provider;
    }
}
