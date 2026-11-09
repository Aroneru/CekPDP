<?php

declare(strict_types=1);

namespace App\Domain\Ai;

/**
 * A quote a provider offers as evidence, with the section it says the quote
 * comes from. Unverified until EvidenceVerifier has checked it.
 */
final readonly class EvidenceQuote
{
    public function __construct(
        public string $sectionId,
        public string $quote,
    ) {}
}
