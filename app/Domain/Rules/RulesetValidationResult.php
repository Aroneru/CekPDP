<?php

declare(strict_types=1);

namespace App\Domain\Rules;

final readonly class RulesetValidationResult
{
    /**
     * @param  list<string>  $errors  problems that make the ruleset unusable
     * @param  list<string>  $warnings  problems worth fixing that don't block loading
     */
    public function __construct(
        public array $errors,
        public array $warnings,
    ) {}

    public function isValid(): bool
    {
        return $this->errors === [];
    }
}
