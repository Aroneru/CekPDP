<?php

declare(strict_types=1);

namespace App\Domain\Document;

/**
 * A sentence or list item of the policy, with byte offsets into the cleaned
 * policy text. The text is always a verbatim substring of the policy.
 *
 * Units of the same list (the sentence ending in ":" that introduces it and
 * the items that follow) share a $listId, so they can be read together.
 */
final readonly class TextUnit
{
    public function __construct(
        public string $sectionId,
        public string $text,
        public int $start,
        public int $end,
        public ?int $listId = null,
    ) {}
}
