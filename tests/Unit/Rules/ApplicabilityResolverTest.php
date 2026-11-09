<?php

declare(strict_types=1);

use App\Domain\Rules\ApplicabilityResolver;
use App\Domain\Rules\ContextProfile;
use App\Domain\Rules\ResolvedRule;
use App\Domain\Rules\RulesetRepository;
use App\Enums\RequirementType;

beforeEach(function (): void {
    $this->ruleset = app(RulesetRepository::class)->active();
});

/**
 * @param  array<string, string>  $answers
 * @return array<string, ResolvedRule>
 */
function resolveRules(array $answers = []): array
{
    $ruleset = app(RulesetRepository::class)->active();
    $resolved = app(ApplicabilityResolver::class)->resolve($ruleset, ContextProfile::fromInput($ruleset, $answers));

    return array_combine(array_map(fn (ResolvedRule $rule) => $rule->rule->id, $resolved), $resolved);
}

/**
 * @param  array<string, ResolvedRule>  $rules
 * @return list<string>
 */
function idsWhere(array $rules, callable $predicate): array
{
    return array_values(array_keys(array_filter($rules, $predicate)));
}

it('applies every rule with its own type when all answers are "not sure"', function (): void {
    $rules = resolveRules();

    expect(idsWhere($rules, fn (ResolvedRule $rule) => ! $rule->applicable))->toBe([])
        ->and(idsWhere($rules, fn (ResolvedRule $rule) => $rule->appliedOverride !== null))->toBe([])
        ->and(idsWhere($rules, fn (ResolvedRule $rule) => $rule->isLegalRequirement()))->toHaveCount(21);
});

it('drops context-specific rules when the answers rule them out', function (): void {
    $rules = resolveRules([
        'specific_data' => 'no',
        'children' => 'no',
        'international_transfer' => 'no',
        'automated_decisions' => 'no',
    ]);

    expect(idsWhere($rules, fn (ResolvedRule $rule) => ! $rule->applicable))
        ->toBe(['PDP-DATA-03', 'PDP-XFER-01', 'PDP-XFER-02', 'PDP-RIGHT-05', 'PDP-CHILD-01']);
});

it('applies the specific-data rule when only children\'s data is processed', function (): void {
    $rules = resolveRules(['specific_data' => 'no', 'children' => 'yes']);

    expect($rules['PDP-DATA-03']->applicable)->toBeTrue()
        ->and($rules['PDP-CHILD-01']->applicable)->toBeTrue();
});

it('turns rules based only on Pasal 21 into best practices when consent is not relied on', function (): void {
    $rules = resolveRules(['consent_basis' => 'no']);

    expect(idsWhere($rules, fn (ResolvedRule $rule) => ! $rule->applicable))
        ->toBe(['PDP-RIGHT-04', 'PDP-CONSENT-01'])
        ->and(idsWhere($rules, fn (ResolvedRule $rule) => $rule->appliedOverride !== null))->toBe([
            'PDP-DATA-02', 'PDP-RET-01', 'PDP-RET-02',
            'PDP-RIGHT-01', 'PDP-RIGHT-02', 'PDP-RIGHT-03', 'PDP-RIGHT-05', 'PDP-RIGHT-06', 'PDP-RIGHT-07', 'PDP-RIGHT-08',
            'PDP-CHG-01',
        ])
        ->and($rules['PDP-RET-01']->requirementType)->toBe(RequirementType::BestPractice)
        ->and($rules['PDP-RET-01']->appliedOverride?->reason->en)->toContain('Article 21');

    // Rules grounded in Pasal 5, 16 or 27 stay legal requirements.
    expect(idsWhere($rules, fn (ResolvedRule $rule) => $rule->isLegalRequirement()))->toBe([
        'PDP-ID-01', 'PDP-ID-02', 'PDP-DATA-01', 'PDP-DATA-03', 'PDP-DATA-04',
        'PDP-PURP-01', 'PDP-BASIS-01', 'PDP-SHARE-01', 'PDP-XFER-01',
    ]);
});
