<?php

declare(strict_types=1);

namespace App\Domain\Rules;

use App\Enums\ReferenceRole;

/**
 * A rule's link to a provision, optionally pinpointing a letter or phrase.
 */
final readonly class RuleReference
{
    public function __construct(
        public string $provisionKey,
        public ReferenceRole $role,
        public ?LocalizedText $pinpoint,
    ) {}

    public function isBasis(): bool
    {
        return $this->role === ReferenceRole::Basis;
    }
}
