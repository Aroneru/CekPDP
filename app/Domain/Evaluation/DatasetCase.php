<?php

declare(strict_types=1);

namespace App\Domain\Evaluation;

use App\Domain\Rules\ContextProfile;

/**
 * One fictional policy and the expected result of every rule for it.
 */
final readonly class DatasetCase
{
    /**
     * @param  array<string, ExpectedResult>  $expected  keyed by rule ID
     */
    public function __construct(
        public string $slug,
        public string $title,
        public string $language,
        public string $description,
        public string $fixturePath,
        public ContextProfile $context,
        public array $expected,
    ) {}

    public function policyText(): string
    {
        return (string) file_get_contents($this->fixturePath);
    }

    /**
     * @return list<ExpectedResult>
     */
    public function applicableResults(): array
    {
        return array_values(array_filter(
            $this->expected,
            static fn (ExpectedResult $result): bool => $result->applicable,
        ));
    }
}
