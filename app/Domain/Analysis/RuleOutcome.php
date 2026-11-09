<?php

declare(strict_types=1);

namespace App\Domain\Analysis;

use App\Domain\Evidence\VerifiedEvaluation;
use App\Domain\Rules\ResolvedRule;
use App\Enums\RuleStatus;

/**
 * The final result of one active rule: how it applies to this analysis and,
 * when applicable, its verified evaluation.
 */
final readonly class RuleOutcome
{
    public function __construct(
        public ResolvedRule $resolved,
        public ?VerifiedEvaluation $evaluation,
    ) {}

    public function status(): ?RuleStatus
    {
        return $this->evaluation?->status;
    }
}
