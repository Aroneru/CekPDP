<?php

declare(strict_types=1);

use App\Domain\Rules\RulesetValidator;

beforeEach(function (): void {
    $this->validator = app(RulesetValidator::class);
    $this->data = activeRulesetData();
});

/**
 * @param  array<string, mixed>  $data
 */
function validationErrors(array $data): string
{
    return implode("\n", app(RulesetValidator::class)->validate($data)->errors);
}

it('accepts the active ruleset without errors or warnings', function (): void {
    $result = $this->validator->validate($this->data);

    expect($result->errors)->toBe([])
        ->and($result->warnings)->toBe([]);
});

it('reports schema violations', function (): void {
    unset($this->data['version']);

    expect(validationErrors($this->data))->toContain('schema');
});

it('requires an interpretation note on interpretive rules', function (): void {
    unset($this->data['rules'][ruleIndex($this->data, 'PDP-ID-02')]['interpretation_note']);

    expect(validationErrors($this->data))->toContain('schema');
});

it('forbids a legal mapping on best practices', function (): void {
    $this->data['rules'][ruleIndex($this->data, 'PDP-SEC-01')]['legal_mapping'] = 'direct';

    expect(validationErrors($this->data))->toContain('schema');
});

it('rejects duplicate rule IDs', function (): void {
    $this->data['rules'][] = $this->data['rules'][0];

    expect(validationErrors($this->data))->toContain('duplicate rule ID');
});

it('rejects references to unknown provisions', function (): void {
    $this->data['rules'][0]['references'][0]['provision'] = 'p999';

    expect(validationErrors($this->data))->toContain('unknown provision p999');
});

it('requires a basis reference on legal requirements', function (): void {
    $index = ruleIndex($this->data, 'PDP-ID-01');
    $this->data['rules'][$index]['references'][0]['role'] = 'related';

    expect(validationErrors($this->data))->toContain('PDP-ID-01: a legal requirement needs at least one basis reference');
});

it('does not let a best practice cite a basis', function (): void {
    $index = ruleIndex($this->data, 'PDP-SEC-01');
    $this->data['rules'][$index]['references'][0]['role'] = 'basis';

    expect(validationErrors($this->data))->toContain('PDP-SEC-01: a best practice may only cite related provisions');
});

it('rejects unknown type overrides', function (): void {
    $this->data['rules'][ruleIndex($this->data, 'PDP-RET-01')]['type_overrides'] = ['no_such_override'];

    expect(validationErrors($this->data))->toContain('unknown type override no_such_override');
});

it('rejects conditions on unknown context questions', function (): void {
    $this->data['rules'][ruleIndex($this->data, 'PDP-XFER-01')]['applies_when']['any'][0]['context'] = 'planet';

    expect(validationErrors($this->data))->toContain('unknown context question planet');
});

it('rejects answers a context question does not offer', function (): void {
    $this->data['contexts']['children']['options'] = ['yes', 'no'];

    expect(validationErrors($this->data))->toContain('not offered by context question children');
});

it('rejects signal patterns that do not compile', function (): void {
    $this->data['rules'][0]['signals']['topic']['id'][] = '([unclosed';

    expect(validationErrors($this->data))->toContain('signal pattern does not compile');
});

it('requires legal texts to come from an official source', function (): void {
    $this->data['provisions']['p5']['source'] = 'pasal-id';

    expect(validationErrors($this->data))->toContain('provision p5: legal text must come from an official source');
});

it('rejects verification dates after the last review', function (): void {
    $this->data['provisions']['p5']['verified_on'] = '2030-01-01';

    expect(validationErrors($this->data))->toContain('provision p5: verified_on is after');
});

it('rejects court decisions that affect unknown provisions', function (): void {
    $this->data['instrument']['court_decisions'][0]['affects'] = 'p999';

    expect(validationErrors($this->data))->toContain('unknown provision p999');
});

it('warns about provisions no rule cites', function (): void {
    $this->data['provisions']['p-unused'] = $this->data['provisions']['p5'];

    $result = $this->validator->validate($this->data);

    expect($result->isValid())->toBeTrue()
        ->and($result->warnings)->toContain('provision p-unused: not referenced by any rule');
});
