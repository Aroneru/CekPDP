<?php

declare(strict_types=1);

namespace App\Domain\Rules;

use App\Enums\ContextAnswer;

/**
 * "The answer to context question X is one of these values."
 */
final readonly class ContextCondition
{
    /**
     * @param  list<ContextAnswer>  $answers
     */
    public function __construct(
        public string $context,
        public array $answers,
    ) {}

    public function isSatisfiedBy(ContextProfile $profile): bool
    {
        return in_array($profile->answer($this->context), $this->answers, true);
    }
}
