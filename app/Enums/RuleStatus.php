<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The outcome of checking one rule against a policy.
 */
enum RuleStatus: string
{
    case Addressed = 'addressed';
    case PartiallyAddressed = 'partially_addressed';
    case NotFound = 'not_found';
    case NeedsHumanReview = 'needs_human_review';

    /**
     * Whether the policy covers the topic at all (used for false-positive and
     * false-negative counts in the evaluation).
     */
    public function isFound(): bool
    {
        return $this === self::Addressed || $this === self::PartiallyAddressed;
    }
}
