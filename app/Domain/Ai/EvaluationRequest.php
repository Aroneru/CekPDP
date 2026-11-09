<?php

declare(strict_types=1);

namespace App\Domain\Ai;

use App\Domain\Document\PolicyDocument;
use App\Domain\Rules\Rule;

/**
 * One batch of rules to evaluate against the policy.
 */
final readonly class EvaluationRequest
{
    /**
     * @param  non-empty-list<Rule>  $rules
     */
    public function __construct(
        public PolicyDocument $document,
        public array $rules,
        public string $rulesetVersion,
        public int $attempt = 1,
    ) {}

    /**
     * @return list<string>
     */
    public function ruleIds(): array
    {
        return array_map(static fn (Rule $rule): string => $rule->id, $this->rules);
    }

    public function retry(): self
    {
        return new self($this->document, $this->rules, $this->rulesetVersion, $this->attempt + 1);
    }
}
