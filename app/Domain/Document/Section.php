<?php

declare(strict_types=1);

namespace App\Domain\Document;

/**
 * A contiguous part of the policy with a stable ID (S01, S02, … in document
 * order). Offsets are byte offsets into the cleaned policy text, so
 * substr($text, $start, $end - $start) === $this->text.
 */
final readonly class Section
{
    public function __construct(
        public string $id,
        public ?string $heading,
        public int $start,
        public int $end,
        public string $text,
        public bool $continued = false,
    ) {}

    public function contains(int $offset): bool
    {
        return $offset >= $this->start && $offset < $this->end;
    }

    /**
     * Stored form. The text itself is not repeated; it is cut from the policy
     * text when the document is restored.
     *
     * @return array{id: string, heading: string|null, start: int, end: int, continued: bool}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'heading' => $this->heading,
            'start' => $this->start,
            'end' => $this->end,
            'continued' => $this->continued,
        ];
    }
}
