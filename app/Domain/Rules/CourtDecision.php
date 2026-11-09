<?php

declare(strict_types=1);

namespace App\Domain\Rules;

use Carbon\CarbonImmutable;

/**
 * A Constitutional Court decision that changes how a provision is read.
 */
final readonly class CourtDecision
{
    public function __construct(
        public string $reference,
        public CarbonImmutable $pronouncedOn,
        public string $affectsProvision,
        public LocalizedText $effect,
        public string $sourceKey,
        public CarbonImmutable $verifiedOn,
    ) {}
}
