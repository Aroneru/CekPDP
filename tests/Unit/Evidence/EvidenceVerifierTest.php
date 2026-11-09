<?php

declare(strict_types=1);

use App\Domain\Ai\EvidenceQuote;
use App\Domain\Ai\RuleEvaluation;
use App\Domain\Evidence\EvidenceVerifier;
use App\Domain\Rules\LocalizedText;
use App\Enums\DowngradeReason;
use App\Enums\EvaluationSource;
use App\Enums\RuleStatus;

beforeEach(function (): void {
    $this->document = policyDocument("## Data\n\nKami mengumpulkan nama dan email.\n\n## Retensi\n\nKami menyimpan data selama 5 tahun.");
    $this->verifier = new EvidenceVerifier;

    $this->evaluation = static fn (RuleStatus $status, array $quotes, EvaluationSource $source = EvaluationSource::Provider): RuleEvaluation => new RuleEvaluation(
        ruleId: 'PDP-RET-01',
        status: $status,
        reason: new LocalizedText('Alasan.', 'Reason.'),
        evidence: array_map(static fn (array $quote): EvidenceQuote => new EvidenceQuote($quote[0], $quote[1]), $quotes),
        source: $source,
    );
});

it('verifies a quote in the cited section and records where it is', function (): void {
    $result = $this->verifier->verify($this->document, ($this->evaluation)(RuleStatus::Addressed, [['S02', 'menyimpan data selama 5 tahun']]));
    $quote = $result->quotes[0];

    expect($result->status)->toBe(RuleStatus::Addressed)
        ->and($result->wasDowngraded())->toBeFalse()
        ->and($quote->verified)->toBeTrue()
        ->and($quote->sectionId)->toBe('S02')
        ->and(substr($this->document->text, (int) $quote->start, (int) $quote->end - (int) $quote->start))->toBe('menyimpan data selama 5 tahun');
});

it('finds a quote in another section and corrects the section', function (): void {
    $result = $this->verifier->verify($this->document, ($this->evaluation)(RuleStatus::Addressed, [['S01', 'menyimpan data selama 5 tahun']]));

    expect($result->status)->toBe(RuleStatus::Addressed)
        ->and($result->quotes[0]->sectionId)->toBe('S02')
        ->and($result->quotes[0]->sectionWasCorrected())->toBeTrue()
        ->and($result->summary->sectionCorrections)->toBe(1);
});

it('downgrades a result whose quote is not in the policy, keeping what the provider said', function (array $quotes): void {
    $result = $this->verifier->verify($this->document, ($this->evaluation)(RuleStatus::Addressed, $quotes));

    expect($result->status)->toBe(RuleStatus::NeedsHumanReview)
        ->and($result->evaluation->status)->toBe(RuleStatus::Addressed)
        ->and($result->summary->downgrade)->toBe(DowngradeReason::UnverifiedQuote)
        ->and($result->summary->failed())->toBe(1);
})->with([
    'invented quote' => [[['S02', 'Kami menyimpan data selama 10 tahun.']]],
    'paraphrase with an ellipsis' => [[['S02', 'Kami menyimpan … 5 tahun.']]],
    'case changed' => [[['S02', 'KAMI MENYIMPAN DATA']]],
    'one good, one invented' => [[['S02', 'selama 5 tahun'], ['S01', 'kami menjual data']]],
    'punctuation only' => [[['S02', '.']]],
]);

it('downgrades a positive result without any quote', function (RuleStatus $status): void {
    $result = $this->verifier->verify($this->document, ($this->evaluation)($status, []));

    expect($result->status)->toBe(RuleStatus::NeedsHumanReview)
        ->and($result->summary->downgrade)->toBe(DowngradeReason::MissingEvidence);
})->with([RuleStatus::Addressed, RuleStatus::PartiallyAddressed]);

it('accepts results that need no quote', function (RuleStatus $status, EvaluationSource $source): void {
    $result = $this->verifier->verify($this->document, ($this->evaluation)($status, [], $source));

    expect($result->status)->toBe($status)
        ->and($result->wasDowngraded())->toBeFalse();
})->with([
    'not found' => [RuleStatus::NotFound, EvaluationSource::Provider],
    'needs review' => [RuleStatus::NeedsHumanReview, EvaluationSource::Provider],
    'language detection' => [RuleStatus::Addressed, EvaluationSource::LanguageCheck],
]);
