<?php

declare(strict_types=1);

namespace App\Domain\Rules;

use Carbon\CarbonImmutable;

/**
 * A provision of the law (or its Elucidation), quoted from the official copy and
 * verified word for word against the page image.
 */
final readonly class Provision
{
    public function __construct(
        public string $key,
        public LocalizedText $citation,
        public int $article,
        public string $pdfPage,
        public LocalizedText $text,
        public string $sourceKey,
        public CarbonImmutable $verifiedOn,
        public ?string $modifiedBy,
    ) {}
}
