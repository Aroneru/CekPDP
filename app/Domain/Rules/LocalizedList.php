<?php

declare(strict_types=1);

namespace App\Domain\Rules;

/**
 * A list of strings in Bahasa Indonesia and English.
 */
final readonly class LocalizedList
{
    /**
     * @param  list<string>  $id
     * @param  list<string>  $en
     */
    public function __construct(
        public array $id,
        public array $en,
    ) {}

    /**
     * @return list<string>
     */
    public function in(string $locale): array
    {
        return $locale === 'en' ? $this->en : $this->id;
    }

    /**
     * @return list<string>
     */
    public function all(): array
    {
        return [...$this->id, ...$this->en];
    }

    public function isEmpty(): bool
    {
        return $this->id === [] && $this->en === [];
    }
}
