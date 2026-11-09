<?php

declare(strict_types=1);

namespace App\Domain\Ai;

/**
 * All rule evaluations for one policy, plus how they were obtained.
 */
final readonly class EvaluationOutcome
{
    /**
     * @param  array<string, RuleEvaluation>  $evaluations  keyed by rule ID
     * @param  list<string>  $models  models that answered, in order of first use
     */
    public function __construct(
        public array $evaluations,
        public string $provider,
        public array $models,
        public int $requests,
        public int $retries,
    ) {}

    public function for(string $ruleId): ?RuleEvaluation
    {
        return $this->evaluations[$ruleId] ?? null;
    }
}
