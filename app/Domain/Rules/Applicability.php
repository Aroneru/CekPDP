<?php

declare(strict_types=1);

namespace App\Domain\Rules;

/**
 * When a rule applies: always, or when any of the listed context conditions holds.
 */
final readonly class Applicability
{
    /**
     * @param  list<ContextCondition>  $anyOf
     */
    private function __construct(
        public bool $always,
        public array $anyOf,
    ) {}

    public static function always(): self
    {
        return new self(true, []);
    }

    /**
     * @param  list<ContextCondition>  $conditions
     */
    public static function anyOf(array $conditions): self
    {
        return new self(false, $conditions);
    }

    public function isSatisfiedBy(ContextProfile $profile): bool
    {
        if ($this->always) {
            return true;
        }

        foreach ($this->anyOf as $condition) {
            if ($condition->isSatisfiedBy($profile)) {
                return true;
            }
        }

        return false;
    }
}
