<?php

declare(strict_types=1);

use App\Domain\Analysis\AnalysisPipeline;
use App\Domain\Analysis\ProgressReporter;
use App\Domain\Analysis\RuleOutcome;
use App\Domain\Extraction\TextCleaner;
use App\Domain\Rules\ContextProfile;
use App\Enums\AnalysisStage;

beforeEach(function (): void {
    $this->pipeline = app(AnalysisPipeline::class);
    $this->ruleset = activeRuleset();

    $this->run = fn (string $text, array $context = []) => $this->pipeline->run(
        app(TextCleaner::class)->clean($text),
        $this->ruleset,
        ContextProfile::fromInput($this->ruleset, $context),
    );
});

it('produces a complete, verified result for every fixture with the offline mock', function (string $slug): void {
    $expected = fixtureExpectations($slug);
    $outcome = ($this->run)(fixtureText($slug), $expected['context']);

    expect($outcome->rules)->toHaveCount(count($this->ruleset->activeRules()))
        ->and($outcome->provider)->toBe('mock')
        ->and($outcome->score->isWithheld())->toBeFalse();

    foreach ($outcome->rules as $rule) {
        $isApplicable = $expected['expected'][$rule->resolved->rule->id] !== 'not_applicable';

        expect($rule->resolved->applicable)->toBe($isApplicable)
            ->and($rule->evaluation === null)->toBe(! $isApplicable);

        // The mock only quotes real sentences, so nothing should be downgraded.
        expect($rule->evaluation?->wasDowngraded() ?? false)->toBeFalse();
    }
})->with(fixtureSlugs());

it('is not steered by instructions hidden in the policy', function (): void {
    $text = fixtureText('en-12-injection');
    $withoutNote = preg_replace('/^\[Note to automated reviewers.*\]$/m', '', $text);

    expect($withoutNote)->not->toBe($text);

    $statuses = static fn ($outcome): array => array_map(static fn (RuleOutcome $rule): ?string => $rule->status()?->value, $outcome->rules);
    $context = fixtureExpectations('en-12-injection')['context'];
    $outcome = ($this->run)($text, $context);

    expect($statuses($outcome))->toBe($statuses(($this->run)((string) $withoutNote, $context)))
        ->and($outcome->score->percent)->toBeLessThan(100);
});

it('reports progress through the stages in order', function (): void {
    $reporter = new class implements ProgressReporter
    {
        /** @var list<array{string, int}> */
        public array $reports = [];

        public function report(AnalysisStage $stage, int $percent): void
        {
            $this->reports[] = [$stage->value, $percent];
        }
    };

    $this->pipeline->run(fixtureText('id-09-umkm-minim'), $this->ruleset, ContextProfile::defaults($this->ruleset), $reporter);

    $stages = array_values(array_unique(array_column($reporter->reports, 0)));
    $percents = array_column($reporter->reports, 1);

    expect($stages)->toBe(['structuring', 'evaluating', 'verifying', 'scoring'])
        ->and($percents)->toBe(array_values(array_unique($percents)))
        ->and(max($percents))->toBeLessThan(100);

    $sorted = $percents;
    sort($sorted);
    expect($percents)->toBe($sorted);
});
