<?php

declare(strict_types=1);

namespace App\Domain\Rules;

use App\Enums\RulesetStatus;
use Carbon\CarbonImmutable;

/**
 * A versioned, reviewed checklist built on verified legal provisions.
 */
final readonly class Ruleset
{
    /**
     * @param  array<string, Source>  $sources
     * @param  array<string, ContextQuestion>  $contexts
     * @param  array<string, TypeOverride>  $overrides
     * @param  array<string, Provision>  $provisions
     * @param  list<Rule>  $rules
     */
    public function __construct(
        public string $id,
        public string $version,
        public RulesetStatus $status,
        public CarbonImmutable $lastReviewed,
        public RulesetReview $review,
        public LocalizedText $scope,
        public Instrument $instrument,
        public array $sources,
        public array $contexts,
        public array $overrides,
        public array $provisions,
        public array $rules,
    ) {}

    /**
     * Rules in the active checklist, in display order. Rules awaiting legal
     * review are never used in analyses.
     *
     * @return list<Rule>
     */
    public function activeRules(): array
    {
        return array_values(array_filter(
            $this->rules,
            static fn (Rule $rule): bool => $rule->isActive(),
        ));
    }

    public function hasRule(string $id): bool
    {
        foreach ($this->rules as $rule) {
            if ($rule->id === $id) {
                return true;
            }
        }

        return false;
    }

    public function rule(string $id): Rule
    {
        foreach ($this->rules as $rule) {
            if ($rule->id === $id) {
                return $rule;
            }
        }

        throw RulesetException::unknownRule($this->version, $id);
    }

    public function provision(string $key): Provision
    {
        return $this->provisions[$key] ?? throw RulesetException::unknownProvision($this->version, $key);
    }

    public function source(string $key): Source
    {
        return $this->sources[$key] ?? throw RulesetException::unknownSource($this->version, $key);
    }

    public function override(string $key): TypeOverride
    {
        return $this->overrides[$key] ?? throw RulesetException::unknownOverride($this->version, $key);
    }
}
