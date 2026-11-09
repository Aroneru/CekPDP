<?php

declare(strict_types=1);

use App\Domain\Ai\EvaluationRequest;
use App\Domain\Ai\MockEvaluator;
use App\Domain\Ai\ResponseValidator;
use App\Domain\Document\SentenceSplitter;

beforeEach(function (): void {
    $this->mock = new MockEvaluator(new SentenceSplitter);

    /** Evaluates one rule against a text and returns the raw result. */
    $this->evaluate = function (string $text, string $ruleId): array {
        $request = new EvaluationRequest(policyDocument($text), [activeRuleset()->rule($ruleId)], '1.0.0');

        return $this->mock->evaluate($request)->payload['results'][0];
    };
});

it('finds nothing when no sentence is about the rule', function (): void {
    $result = ($this->evaluate)(fixtureText('id-09-umkm-minim'), 'PDP-RIGHT-07');

    expect($result['status'])->toBe('not_found')
        ->and($result['evidence'])->toBe([]);
});

it('says Addressed when every element is present', function (): void {
    $result = ($this->evaluate)('Data akun disimpan selama 5 tahun setelah akun ditutup, lalu dihapus.', 'PDP-RET-01');

    expect($result['status'])->toBe('addressed')
        ->and($result['evidence'])->toBe([[
            'section_id' => 'S01',
            'quote' => 'Data akun disimpan selama 5 tahun setelah akun ditutup, lalu dihapus.',
        ]]);
});

it('says Partially addressed when an element is missing', function (): void {
    $result = ($this->evaluate)('Data Anda disimpan di server yang aman dan terlindungi.', 'PDP-RET-01');

    expect($result['status'])->toBe('partially_addressed')
        ->and($result['reason_en'])->toContain('not every element');
});

it('caps the result and quotes a statement that conflicts with the law first', function (): void {
    $text = 'Kami akan memberi tahu Anda sebelum perubahan berlaku. Perubahan berlaku sejak diunggah di situs kami.';

    $result = ($this->evaluate)($text, 'PDP-CHG-01');

    expect($result['status'])->toBe('partially_addressed')
        ->and($result['reason_en'])->toContain('may not be consistent')
        ->and($result['evidence'][0]['quote'])->toBe('Perubahan berlaku sejak diunggah di situs kami.');
});

it('reads list items together with the sentence that introduces them', function (): void {
    $text = "Sebagai Subjek Data Pribadi, Anda berhak:\n\n- memperbarui dan memperbaiki Data Pribadi yang tidak akurat;\n- mendapatkan akses ke data Anda.";

    $result = ($this->evaluate)($text, 'PDP-RIGHT-01');

    expect($result['status'])->toBe('addressed')
        ->and(array_column($result['evidence'], 'quote'))->toBe([
            'Sebagai Subjek Data Pribadi, Anda berhak:',
            'memperbarui dan memperbaiki Data Pribadi yang tidak akurat;',
        ]);
});

it('cuts very long sentences to a verbatim window around the match', function (): void {
    $padding = str_repeat('kata pengisi yang panjang ', 30);
    $text = 'Data '.$padding.'disimpan selama 5 tahun '.$padding.'sampai selesai.';

    $quote = ($this->evaluate)($text, 'PDP-RET-01')['evidence'][0]['quote'];

    expect(strlen($quote))->toBeLessThanOrEqual(480)
        ->and($text)->toContain($quote)
        ->and($quote)->toContain('disimpan');
});

it('gives valid, verbatim, deterministic output for every rule on every fixture', function (string $slug): void {
    $ruleset = activeRuleset();
    $document = policyDocument(fixtureText($slug));
    $request = new EvaluationRequest($document, $ruleset->activeRules(), $ruleset->version);

    $payload = $this->mock->evaluate($request)->payload;

    expect($this->mock->evaluate($request)->payload)->toBe($payload);

    // Passes the same validation as any provider.
    $evaluations = app(ResponseValidator::class)->validate($payload, $request);
    expect($evaluations)->toHaveCount(count($ruleset->activeRules()));

    foreach ($evaluations as $evaluation) {
        expect(count($evaluation->evidence))->toBeLessThanOrEqual(3);

        foreach ($evaluation->evidence as $quote) {
            expect($document->section($quote->sectionId)?->text)->toContain($quote->quote);
        }
    }
})->with(fixtureSlugs());
