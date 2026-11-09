<?php

declare(strict_types=1);

namespace App\Domain\Rules;

use Carbon\CarbonImmutable;

/**
 * The law the ruleset is based on, with the decisions and regulations that
 * affect it.
 */
final readonly class Instrument
{
    /**
     * @param  list<CourtDecision>  $courtDecisions
     * @param  list<ImplementingRegulation>  $implementingRegulations
     */
    public function __construct(
        public string $key,
        public LocalizedText $title,
        public CarbonImmutable $enactedOn,
        public CarbonImmutable $promulgatedOn,
        public CarbonImmutable $inForceFrom,
        public CarbonImmutable $transitionEndedOn,
        public string $gazette,
        public array $courtDecisions,
        public array $implementingRegulations,
    ) {}
}
