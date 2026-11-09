<?php

declare(strict_types=1);

use App\Domain\Ai\EvaluationRequest;
use App\Domain\Ai\InvalidProviderOutput;
use App\Domain\Ai\ResponseValidator;
use App\Enums\RuleStatus;

beforeEach(function (): void {
    $ruleset = activeRuleset();
    $this->request = new EvaluationRequest(
        policyDocument('PT Contoh Abadi menyimpan data selama 5 tahun.'),
        [$ruleset->rule('PDP-ID-01'), $ruleset->rule('PDP-RET-01')],
        $ruleset->version,
    );
    $this->validator = app(ResponseValidator::class);

    $this->result = static fn (string $ruleId, string $status = 'not_found', array $evidence = [], array $extra = []): array => [
        'rule_id' => $ruleId,
        'status' => $status,
        'reason_id' => 'Alasan.',
        'reason_en' => 'Reason.',
        'evidence' => $evidence,
        ...$extra,
    ];
});

it('accepts valid output and returns it in the order the rules were requested', function (): void {
    $payload = ['results' => [
        ($this->result)('PDP-RET-01', 'addressed', [['section_id' => 'S01', 'quote' => 'menyimpan data selama 5 tahun']]),
        ($this->result)('PDP-ID-01', 'partially_addressed', [['section_id' => 'S01', 'quote' => 'PT Contoh Abadi']]),
    ]];

    $evaluations = $this->validator->validate($payload, $this->request);

    expect(array_map(static fn ($evaluation): string => $evaluation->ruleId, $evaluations))->toBe(['PDP-ID-01', 'PDP-RET-01'])
        ->and($evaluations[1]->status)->toBe(RuleStatus::Addressed)
        ->and($evaluations[1]->reason->en)->toBe('Reason.')
        ->and($evaluations[1]->evidence[0]->quote)->toBe('menyimpan data selama 5 tahun');
});

it('rejects output that breaks the schema or the request', function (Closure $payload, string $problem): void {
    try {
        $this->validator->validate($payload($this->result), $this->request);
        $this->fail('Expected InvalidProviderOutput');
    } catch (InvalidProviderOutput $exception) {
        expect(implode("\n", $exception->errors))->toContain($problem);
    }
})->with([
    'not an object' => [fn (): string => 'Here are the results!', 'schema /'],
    'no results key' => [fn (): array => ['answers' => []], 'schema /'],
    'unknown status' => [fn (Closure $r): array => ['results' => [$r('PDP-ID-01', 'compliant'), $r('PDP-RET-01')]], 'schema /results/0/status'],
    'extra property' => [fn (Closure $r): array => ['results' => [$r('PDP-ID-01', extra: ['score' => 100]), $r('PDP-RET-01')]], 'schema /results/0'],
    'too many quotes' => [fn (Closure $r): array => ['results' => [$r('PDP-ID-01', 'addressed', array_fill(0, 4, ['section_id' => 'S01', 'quote' => 'PT'])), $r('PDP-RET-01')]], 'schema /results/0/evidence'],
    'quote too long' => [fn (Closure $r): array => ['results' => [$r('PDP-ID-01', 'addressed', [['section_id' => 'S01', 'quote' => str_repeat('x', 601)]]), $r('PDP-RET-01')]], 'schema /results/0/evidence/0/quote'],
    'missing rule' => [fn (Closure $r): array => ['results' => [$r('PDP-ID-01')]], 'rule PDP-RET-01 is missing'],
    'rule not requested' => [fn (Closure $r): array => ['results' => [$r('PDP-ID-01'), $r('PDP-RET-01'), $r('PDP-LANG-01')]], 'PDP-LANG-01 was not requested'],
    'duplicate rule' => [fn (Closure $r): array => ['results' => [$r('PDP-ID-01'), $r('PDP-ID-01'), $r('PDP-RET-01')]], 'appears more than once'],
    'evidence on not found' => [fn (Closure $r): array => ['results' => [$r('PDP-ID-01', 'not_found', [['section_id' => 'S01', 'quote' => 'PT Contoh']]), $r('PDP-RET-01')]], 'must not quote evidence'],
]);

it('never puts quoted text in its error messages', function (): void {
    $payload = ['results' => [
        ($this->result)('PDP-ID-01', 'not_found', [['section_id' => 'S01', 'quote' => 'RAHASIA-PERUSAHAAN-123']]),
        ($this->result)('PDP-RET-01', 'addressed', [['section_id' => 'S1', 'quote' => str_repeat('RAHASIA ', 100)]]),
    ]];

    try {
        $this->validator->validate($payload, $this->request);
        $this->fail('Expected InvalidProviderOutput');
    } catch (InvalidProviderOutput $exception) {
        expect($exception->errors)->not->toBeEmpty()
            ->and(implode("\n", $exception->errors).$exception->getMessage())->not->toContain('RAHASIA');
    }
});
