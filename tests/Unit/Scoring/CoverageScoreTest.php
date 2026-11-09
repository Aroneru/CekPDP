<?php

declare(strict_types=1);

use App\Domain\Ai\RuleEvaluation;
use App\Domain\Analysis\RuleOutcome;
use App\Domain\Evidence\VerificationSummary;
use App\Domain\Evidence\VerifiedEvaluation;
use App\Domain\Rules\LocalizedText;
use App\Domain\Rules\ResolvedRule;
use App\Domain\Scoring\CoverageScore;
use App\Domain\Scoring\CoverageScorer;
use App\Domain\Scoring\StatusCounts;
use App\Enums\RequirementType;
use App\Enums\RuleStatus;
use App\Enums\WithheldReason;

it('applies the published formula and shows the arithmetic', function (): void {
    $score = CoverageScore::calculate(new StatusCounts(addressed: 12, partiallyAddressed: 3, notFound: 2), new StatusCounts);

    expect($score->percent)->toBe(79)
        ->and($score->arithmetic())->toBe('(12 + 0.5 × 3) ÷ 17 = 79%')
        ->and($score->isWithheld())->toBeFalse();
});

it('rounds half up', function (int $addressed, int $partial, int $notFound, int $expected): void {
    $score = CoverageScore::calculate(new StatusCounts($addressed, $partial, $notFound), new StatusCounts);

    expect($score->percent)->toBe($expected);
})->with([
    '12.5% → 13%' => [1, 0, 7, 13],
    '2/3 → 67%' => [2, 0, 1, 67],
    'all addressed' => [5, 0, 0, 100],
    'nothing addressed' => [0, 0, 5, 0],
    'only partial' => [0, 4, 0, 50],
]);

it('leaves rules that need review out of the formula', function (): void {
    $score = CoverageScore::calculate(new StatusCounts(addressed: 8, needsReview: 2), new StatusCounts);

    expect($score->percent)->toBe(100)
        ->and($score->legal->evaluated())->toBe(8)
        ->and($score->reviewSharePercent())->toBe(20);
});

it('withholds the score when more than 20% of legal requirements need review', function (): void {
    $atLimit = CoverageScore::calculate(new StatusCounts(addressed: 8, needsReview: 2), new StatusCounts);
    $overLimit = CoverageScore::calculate(new StatusCounts(addressed: 7, needsReview: 3), new StatusCounts);

    expect($atLimit->isWithheld())->toBeFalse()
        ->and($overLimit->percent)->toBeNull()
        ->and($overLimit->withheld)->toBe(WithheldReason::TooManyReviews)
        ->and($overLimit->arithmetic())->toBeNull();
});

it('withholds the score when no legal requirement applies', function (): void {
    $score = CoverageScore::calculate(new StatusCounts, new StatusCounts(addressed: 3));

    expect($score->withheld)->toBe(WithheldReason::NoLegalRequirements);
});

it('is not affected by best practices', function (): void {
    $legal = new StatusCounts(addressed: 5, notFound: 5);

    expect(CoverageScore::calculate($legal, new StatusCounts(addressed: 10))->percent)
        ->toBe(CoverageScore::calculate($legal, new StatusCounts(notFound: 10))->percent);
});

it('round-trips through its stored form', function (): void {
    $score = CoverageScore::calculate(new StatusCounts(9, 6, 6, 1), new StatusCounts(4, 1, 4, 0));

    expect(CoverageScore::fromArray($score->toArray()))->toEqual($score)
        ->and($score->toArray()['formula'])->toBe('coverage-v1');
});

it('counts only applicable rules, by their effective requirement type', function (): void {
    $rule = activeRuleset()->rule('PDP-RET-01');

    $outcome = static function (bool $applicable, RequirementType $type, ?RuleStatus $status) use ($rule): RuleOutcome {
        $evaluation = $status === null ? null : new VerifiedEvaluation(
            new RuleEvaluation($rule->id, $status, new LocalizedText('a', 'b'), []),
            $status,
            [],
            new VerificationSummary(0, 0, 0, null),
        );

        return new RuleOutcome(new ResolvedRule($rule, $applicable, $type, null), $evaluation);
    };

    $score = (new CoverageScorer)->score([
        $outcome(true, RequirementType::Legal, RuleStatus::Addressed),
        $outcome(true, RequirementType::Legal, RuleStatus::NotFound),
        // A Pasal 21 rule that became a best practice because consent isn't relied on.
        $outcome(true, RequirementType::BestPractice, RuleStatus::NotFound),
        $outcome(false, RequirementType::Legal, null),
    ]);

    expect($score->legal->toArray())->toBe(['addressed' => 1, 'partially_addressed' => 0, 'not_found' => 1, 'needs_human_review' => 0])
        ->and($score->bestPractice->notFound)->toBe(1)
        ->and($score->percent)->toBe(50);
});
