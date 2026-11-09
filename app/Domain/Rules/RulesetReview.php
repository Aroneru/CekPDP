<?php

declare(strict_types=1);

namespace App\Domain\Rules;

use Carbon\CarbonImmutable;

/**
 * Who verified the legal texts and when the project owner approved the ruleset.
 */
final readonly class RulesetReview
{
    public function __construct(
        public string $verifiedBy,
        public CarbonImmutable $verifiedOn,
        public ?CarbonImmutable $approvedOn,
        public ?LocalizedText $approvalNote,
    ) {}
}
