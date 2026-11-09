<?php

declare(strict_types=1);

namespace App\Domain\Document;

use InvalidArgumentException;

/**
 * The cleaned policy text, its detected language and its sections. This is the
 * only view of the policy that providers, evidence checks and reports use.
 */
final readonly class PolicyDocument
{
    /**
     * @param  list<Section>  $sections
     */
    public function __construct(
        public string $text,
        public LanguageProfile $language,
        public array $sections,
    ) {}

    /**
     * Rebuilds a document from its stored text and section offsets.
     *
     * @param  array<array-key, mixed>  $sections  as produced by Section::toArray()
     *
     * @throws InvalidArgumentException when the stored sections don't fit the text
     */
    public static function restore(string $text, array $sections, LanguageProfile $language): self
    {
        $restored = [];

        foreach ($sections as $data) {
            if (! is_array($data) || ! is_string($data['id'] ?? null) || ! is_int($data['start'] ?? null) || ! is_int($data['end'] ?? null)) {
                throw new InvalidArgumentException('Stored sections are malformed.');
            }

            $start = $data['start'];
            $end = $data['end'];

            if ($start < 0 || $end < $start || $end > strlen($text)) {
                throw new InvalidArgumentException('Stored sections do not fit the policy text.');
            }

            $restored[] = new Section(
                id: $data['id'],
                heading: is_string($data['heading'] ?? null) ? $data['heading'] : null,
                start: $start,
                end: $end,
                text: substr($text, $start, $end - $start),
                continued: ($data['continued'] ?? false) === true,
            );
        }

        return new self($text, $language, $restored);
    }

    public function section(string $id): ?Section
    {
        foreach ($this->sections as $section) {
            if ($section->id === $id) {
                return $section;
            }
        }

        return null;
    }

    /**
     * The section containing the given byte offset.
     */
    public function sectionAt(int $offset): ?Section
    {
        foreach ($this->sections as $section) {
            if ($section->contains($offset)) {
                return $section;
            }
        }

        return null;
    }

    public function characterCount(): int
    {
        return TextStats::characters($this->text);
    }

    public function wordCount(): int
    {
        return TextStats::words($this->text);
    }
}
