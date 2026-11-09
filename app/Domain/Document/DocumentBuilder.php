<?php

declare(strict_types=1);

namespace App\Domain\Document;

use SensitiveParameter;

/**
 * Detects the language of cleaned policy text and splits it into sections.
 */
final class DocumentBuilder
{
    public function __construct(
        private readonly LanguageDetector $languages,
        private readonly Sectionizer $sectionizer,
    ) {}

    public function build(#[SensitiveParameter] string $cleanText): PolicyDocument
    {
        return new PolicyDocument(
            text: $cleanText,
            language: $this->languages->detect($cleanText),
            sections: $this->sectionizer->split($cleanText),
        );
    }
}
