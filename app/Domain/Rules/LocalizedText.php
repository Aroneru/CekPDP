<?php

declare(strict_types=1);

namespace App\Domain\Rules;

/**
 * A text in Bahasa Indonesia and English. The Indonesian text is authoritative;
 * English text in the ruleset is an unofficial translation.
 */
final readonly class LocalizedText
{
    public function __construct(
        public string $id,
        public string $en,
    ) {}

    public function in(string $locale): string
    {
        return $locale === 'en' ? $this->en : $this->id;
    }
}
