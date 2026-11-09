<?php

declare(strict_types=1);

namespace App\Domain\Rules;

use Carbon\CarbonImmutable;

/**
 * An implementing regulation of the law. Recorded for transparency; regulations
 * are kept separate from the law and are not used by rules unless in force and
 * verified against an official copy.
 */
final readonly class ImplementingRegulation
{
    public const STATUS_IN_FORCE = 'in_force';

    public const STATUS_PROMULGATED_NOT_IN_FORCE = 'promulgated_not_in_force';

    public const STATUS_NOT_ENACTED = 'not_enacted';

    public function __construct(
        public string $reference,
        public string $status,
        public LocalizedText $note,
        public CarbonImmutable $checkedOn,
        public bool $usedByRules,
    ) {}
}
