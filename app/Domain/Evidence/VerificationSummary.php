<?php

declare(strict_types=1);

namespace App\Domain\Evidence;

use App\Enums\DowngradeReason;

final readonly class VerificationSummary
{
    public function __construct(
        public int $quotes,
        public int $verified,
        public int $sectionCorrections,
        public ?DowngradeReason $downgrade,
    ) {}

    public function failed(): int
    {
        return $this->quotes - $this->verified;
    }

    /**
     * @param  array<array-key, mixed>  $data  as produced by toArray()
     */
    public static function fromArray(array $data): self
    {
        $int = static fn (string $key): int => is_int($data[$key] ?? null) ? $data[$key] : 0;

        return new self(
            quotes: $int('quotes'),
            verified: $int('verified'),
            sectionCorrections: $int('section_corrections'),
            downgrade: is_string($data['downgrade'] ?? null) ? DowngradeReason::tryFrom($data['downgrade']) : null,
        );
    }

    /**
     * @return array{quotes: int, verified: int, failed: int, section_corrections: int, downgrade: string|null}
     */
    public function toArray(): array
    {
        return [
            'quotes' => $this->quotes,
            'verified' => $this->verified,
            'failed' => $this->failed(),
            'section_corrections' => $this->sectionCorrections,
            'downgrade' => $this->downgrade?->value,
        ];
    }
}
