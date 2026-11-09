<?php

declare(strict_types=1);

namespace App\Domain\Rules;

/**
 * Decides, from the context answers, which active rules apply and what
 * requirement type each one has.
 */
final class ApplicabilityResolver
{
    /**
     * @return list<ResolvedRule>
     */
    public function resolve(Ruleset $ruleset, ContextProfile $context): array
    {
        $resolved = [];

        foreach ($ruleset->activeRules() as $rule) {
            $override = $this->matchingOverride($ruleset, $rule, $context);

            $resolved[] = new ResolvedRule(
                rule: $rule,
                applicable: $rule->applicability->isSatisfiedBy($context),
                requirementType: $override !== null ? $override->requirementType : $rule->requirementType,
                appliedOverride: $override,
            );
        }

        return $resolved;
    }

    private function matchingOverride(Ruleset $ruleset, Rule $rule, ContextProfile $context): ?TypeOverride
    {
        foreach ($rule->typeOverrides as $key) {
            $override = $ruleset->override($key);

            if ($override->when->isSatisfiedBy($context)) {
                return $override;
            }
        }

        return null;
    }
}
