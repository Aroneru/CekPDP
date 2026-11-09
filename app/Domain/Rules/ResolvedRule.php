<?php

declare(strict_types=1);

namespace App\Domain\Rules;

use App\Enums\RequirementType;

/**
 * A rule as it applies to one analysis: whether it applies, and its effective
 * requirement type after context overrides.
 */
final readonly class ResolvedRule
{
    public function __construct(
        public Rule $rule,
        public bool $applicable,
        public RequirementType $requirementType,
        public ?TypeOverride $appliedOverride,
    ) {}

    public function isLegalRequirement(): bool
    {
        return $this->applicable && $this->requirementType === RequirementType::Legal;
    }
}
