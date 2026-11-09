<?php

declare(strict_types=1);

namespace App\Domain\Evidence;

/**
 * A quote after verification. When verified, $start and $end are byte offsets
 * of the quote in the policy text and $sectionId is the section that actually
 * contains it, which may differ from the section the provider cited.
 */
final readonly class VerifiedQuote
{
    public function __construct(
        public string $quote,
        public string $citedSectionId,
        public bool $verified,
        public ?string $sectionId = null,
        public ?int $start = null,
        public ?int $end = null,
    ) {}

    public function sectionWasCorrected(): bool
    {
        return $this->verified && $this->sectionId !== $this->citedSectionId;
    }

    /**
     * @param  array<array-key, mixed>  $data  as produced by toArray()
     */
    public static function fromArray(array $data): self
    {
        $int = static fn (string $key): ?int => is_int($data[$key] ?? null) ? $data[$key] : null;

        return new self(
            quote: is_string($data['quote'] ?? null) ? $data['quote'] : '',
            citedSectionId: is_string($data['cited_section_id'] ?? null) ? $data['cited_section_id'] : '',
            verified: ($data['verified'] ?? false) === true,
            sectionId: is_string($data['section_id'] ?? null) ? $data['section_id'] : null,
            start: $int('start'),
            end: $int('end'),
        );
    }

    /**
     * @return array{quote: string, cited_section_id: string, section_id: string|null, start: int|null, end: int|null, verified: bool}
     */
    public function toArray(): array
    {
        return [
            'quote' => $this->quote,
            'cited_section_id' => $this->citedSectionId,
            'section_id' => $this->sectionId,
            'start' => $this->start,
            'end' => $this->end,
            'verified' => $this->verified,
        ];
    }
}
