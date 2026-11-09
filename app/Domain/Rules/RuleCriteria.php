<?php

declare(strict_types=1);

namespace App\Domain\Rules;

use App\Enums\RuleStatus;

/**
 * What each status means for a specific rule. Given to the AI provider and shown
 * in the UI.
 */
final readonly class RuleCriteria
{
    public function __construct(
        public LocalizedText $addressed,
        public LocalizedText $partiallyAddressed,
        public LocalizedText $notFound,
    ) {}

    public function for(RuleStatus $status): ?LocalizedText
    {
        return match ($status) {
            RuleStatus::Addressed => $this->addressed,
            RuleStatus::PartiallyAddressed => $this->partiallyAddressed,
            RuleStatus::NotFound => $this->notFound,
            RuleStatus::NeedsHumanReview => null,
        };
    }
}
