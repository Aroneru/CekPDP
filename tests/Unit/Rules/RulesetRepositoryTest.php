<?php

declare(strict_types=1);

use App\Domain\Rules\RulesetException;
use App\Domain\Rules\RulesetRepository;
use App\Enums\LegalMapping;
use App\Enums\RequirementType;
use App\Enums\RulesetStatus;

beforeEach(function (): void {
    $this->repository = app(RulesetRepository::class);
});

it('loads the active ruleset with its review metadata', function (): void {
    $ruleset = $this->repository->active();

    expect($ruleset->id)->toBe('uu-pdp-27-2022')
        ->and($ruleset->version)->toBe('1.0.0')
        ->and($ruleset->status)->toBe(RulesetStatus::Active)
        ->and($ruleset->lastReviewed->toDateString())->toBe('2026-11-09')
        ->and($ruleset->review->verifiedOn->toDateString())->toBe('2026-11-05')
        ->and($ruleset->review->approvedOn?->toDateString())->toBe('2026-11-09');
});

it('contains 31 active rules: 21 legal requirements and 10 best practices', function (): void {
    $rules = $this->repository->active()->activeRules();

    $legal = array_filter($rules, fn ($rule) => $rule->requirementType === RequirementType::Legal);
    $interpretive = array_filter($rules, fn ($rule) => $rule->legalMapping === LegalMapping::Interpretive);

    expect($rules)->toHaveCount(31)
        ->and($legal)->toHaveCount(21)
        ->and(array_map(fn ($rule) => $rule->id, array_values($interpretive)))->toBe(['PDP-ID-02', 'PDP-DATA-04']);
});

it('resolves every reference to a provision with an official source', function (): void {
    $ruleset = $this->repository->active();

    foreach ($ruleset->rules as $rule) {
        foreach ($rule->references as $reference) {
            $provision = $ruleset->provision($reference->provisionKey);

            expect($ruleset->source($provision->sourceKey)->isOfficial())->toBeTrue();
        }
    }
});

it('builds Pasal.id reader links from the secondary source template', function (): void {
    $ruleset = $this->repository->active();

    expect($ruleset->source('pasal-id')->isOfficial())->toBeFalse()
        ->and($ruleset->source('pasal-id')->articleUrl(21))
        ->toBe('https://pasal.id/peraturan/uu/uu-no-27-tahun-2022/pasal-21');
});

it('lists available versions and caches loaded rulesets', function (): void {
    expect($this->repository->availableVersions())->toContain('1.0.0')
        ->and($this->repository->version('1.0.0'))->toBe($this->repository->active());
});

it('rejects malformed and missing versions', function (string $version, string $message): void {
    expect(fn () => $this->repository->version($version))
        ->toThrow(RulesetException::class, $message);
})->with([
    'not semver' => ['latest', 'Invalid ruleset version'],
    'path traversal' => ['../1.0.0', 'Invalid ruleset version'],
    'missing file' => ['9.9.9', 'Ruleset file not found'],
]);

it('throws for unknown rules and provisions', function (): void {
    $ruleset = $this->repository->active();

    expect(fn () => $ruleset->rule('PDP-NOPE-01'))->toThrow(RulesetException::class)
        ->and(fn () => $ruleset->provision('p999'))->toThrow(RulesetException::class);
});
