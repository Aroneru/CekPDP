<?php

declare(strict_types=1);

namespace App\Domain\Evidence;

use App\Domain\Ai\RuleEvaluation;
use App\Enums\RuleStatus;

/**
 * A rule evaluation after its quotes were checked against the policy.
 * $status is final; $evaluation->status is what the provider said.
 */
final readonly class VerifiedEvaluation
{
    /**
     * @param  list<VerifiedQuote>  $quotes
     */
    public function __construct(
        public RuleEvaluation $evaluation,
        public RuleStatus $status,
        public array $quotes,
        public VerificationSummary $summary,
    ) {}

    public function wasDowngraded(): bool
    {
        return $this->summary->downgrade !== null;
    }
}
