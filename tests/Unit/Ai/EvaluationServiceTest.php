<?php

declare(strict_types=1);

use App\Domain\Ai\EvaluationRequest;
use App\Domain\Ai\EvaluationService;
use App\Domain\Ai\InvalidProviderOutput;
use App\Domain\Ai\LanguageCheck;
use App\Domain\Ai\ResponseValidator;
use App\Domain\Rules\ApplicabilityResolver;
use App\Domain\Rules\ContextProfile;
use App\Enums\EvaluationSource;
use App\Enums\RuleStatus;
use Tests\Support\FakeEvaluator;

beforeEach(function (): void {
    $ruleset = activeRuleset();
    $this->ruleset = $ruleset;
    // With every answer "Not sure", all 31 active rules apply.
    $this->rules = (new ApplicabilityResolver)->resolve($ruleset, ContextProfile::defaults($ruleset));

    $this->service = fn (FakeEvaluator $provider, int $batchSize = 8): EvaluationService => new EvaluationService(
        $provider,
        app(ResponseValidator::class),
        new LanguageCheck,
        $batchSize,
    );
});

it('sends the rules in batches and reports progress after each one', function (): void {
    $provider = FakeEvaluator::answering();
    $progress = [];

    $outcome = ($this->service)($provider)->evaluate(
        policyDocument(fixtureText('id-03-fintech-sedang')),
        $this->rules,
        $this->ruleset->version,
        function (int $done, int $total) use (&$progress): void {
            $progress[] = "{$done}/{$total}";
        },
    );

    // PDP-LANG-01 is decided by language detection, so 30 rules go to the provider.
    expect(array_map(static fn (EvaluationRequest $request): int => count($request->rules), $provider->requests))->toBe([8, 8, 8, 6])
        ->and($progress)->toBe(['1/4', '2/4', '3/4', '4/4'])
        ->and(array_keys($outcome->evaluations))->toBe(array_map(static fn ($rule): string => $rule->rule->id, $this->rules))
        ->and($outcome->provider)->toBe('fake')
        ->and($outcome->models)->toBe(['fake-model'])
        ->and($outcome->requests)->toBe(4)
        ->and($outcome->retries)->toBe(0);
});

it('decides the language rule itself for Indonesian and bilingual policies', function (string $slug): void {
    $provider = FakeEvaluator::answering();

    $outcome = ($this->service)($provider)->evaluate(policyDocument(fixtureText($slug)), $this->rules, '1.0.0');
    $language = $outcome->for('PDP-LANG-01');

    expect($language?->status)->toBe(RuleStatus::Addressed)
        ->and($language?->source)->toBe(EvaluationSource::LanguageCheck);

    foreach ($provider->requests as $request) {
        expect($request->ruleIds())->not->toContain('PDP-LANG-01');
    }
})->with(['id-01-belanja-lengkap', 'bi-11-logistik-dwibahasa']);

it('asks the provider about the language rule for English policies', function (): void {
    $provider = FakeEvaluator::answering();

    $outcome = ($this->service)($provider)->evaluate(policyDocument(fixtureText('en-04-gdpr-style')), $this->rules, '1.0.0');

    expect($outcome->for('PDP-LANG-01')?->source)->toBe(EvaluationSource::Provider)
        ->and(array_sum(array_map(static fn (EvaluationRequest $request): int => count($request->rules), $provider->requests)))->toBe(31);
});

it('flags the language rule for review when the language cannot be detected', function (): void {
    $outcome = ($this->service)(FakeEvaluator::answering())->evaluate(policyDocument('PT Contoh Abadi. privasi@contoh.example'), $this->rules, '1.0.0');

    expect($outcome->for('PDP-LANG-01')?->status)->toBe(RuleStatus::NeedsHumanReview);
});

it('asks once more when the output is invalid, then carries on', function (): void {
    $provider = new FakeEvaluator(static fn (EvaluationRequest $request, int $call): mixed => $call === 1 ? 'not json' : FakeEvaluator::payload($request));

    $outcome = ($this->service)($provider, 50)->evaluate(policyDocument(fixtureText('id-03-fintech-sedang')), $this->rules, '1.0.0');

    expect($provider->requests)->toHaveCount(2)
        ->and($provider->requests[1]->attempt)->toBe(2)
        ->and($outcome->requests)->toBe(2)
        ->and($outcome->retries)->toBe(1);
});

it('fails when the output is still invalid after one retry', function (): void {
    $provider = new FakeEvaluator(static fn (): array => ['results' => []]);

    expect(fn () => ($this->service)($provider, 50)->evaluate(policyDocument(fixtureText('id-03-fintech-sedang')), $this->rules, '1.0.0'))
        ->toThrow(InvalidProviderOutput::class);

    expect($provider->requests)->toHaveCount(2);
});
