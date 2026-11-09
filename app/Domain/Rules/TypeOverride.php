<?php

declare(strict_types=1);

namespace App\Domain\Rules;

use App\Enums\RequirementType;

/**
 * Changes a rule's requirement type for certain context answers, e.g. rules based
 * only on Pasal 21 become best practices when consent isn't relied on.
 */
final readonly class TypeOverride
{
    public function __construct(
        public string $key,
        public ContextCondition $when,
        public RequirementType $requirementType,
        public LocalizedText $reason,
    ) {}
}
