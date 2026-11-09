<?php

declare(strict_types=1);

namespace App\Domain\Rules;

/**
 * Keyword patterns (case-insensitive regular expressions) used by the
 * deterministic mock provider. The AI provider does not use them.
 *
 * - topic: a sentence matching any pattern is about this rule.
 * - complete: every group must match for the rule to count as addressed.
 * - caps: statements that conflict with the law (e.g. "continued use means you
 *   accept the changes") and cap the result at partially addressed.
 */
final readonly class RuleSignals
{
    /**
     * @param  list<LocalizedList>  $complete
     */
    public function __construct(
        public LocalizedList $topic,
        public array $complete,
        public LocalizedList $caps,
    ) {}

    /**
     * @return list<string>
     */
    public function allPatterns(): array
    {
        $patterns = [...$this->topic->all(), ...$this->caps->all()];

        foreach ($this->complete as $group) {
            $patterns = [...$patterns, ...$group->all()];
        }

        return $patterns;
    }
}
