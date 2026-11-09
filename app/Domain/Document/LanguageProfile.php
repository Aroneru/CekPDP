<?php

declare(strict_types=1);

namespace App\Domain\Document;

use App\Enums\DocumentLanguage;

/**
 * The detected language and the evidence behind it: how many common Indonesian
 * and English function words the text contains.
 */
final readonly class LanguageProfile
{
    public function __construct(
        public DocumentLanguage $language,
        public int $indonesianWords,
        public int $englishWords,
    ) {}

    /**
     * Share of the counted function words that are Indonesian (0 to 1), or null
     * when none were found.
     */
    public function indonesianShare(): ?float
    {
        $total = $this->indonesianWords + $this->englishWords;

        return $total === 0 ? null : $this->indonesianWords / $total;
    }

    /**
     * @return array{language: string, indonesian_words: int, english_words: int}
     */
    public function toArray(): array
    {
        return [
            'language' => $this->language->value,
            'indonesian_words' => $this->indonesianWords,
            'english_words' => $this->englishWords,
        ];
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $language = is_string($data['language'] ?? null) ? DocumentLanguage::tryFrom($data['language']) : null;

        return new self(
            language: $language ?? DocumentLanguage::Unknown,
            indonesianWords: is_int($data['indonesian_words'] ?? null) ? $data['indonesian_words'] : 0,
            englishWords: is_int($data['english_words'] ?? null) ? $data['english_words'] : 0,
        );
    }
}
