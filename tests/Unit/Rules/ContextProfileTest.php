<?php

declare(strict_types=1);

use App\Domain\Rules\ContextProfile;
use App\Domain\Rules\RulesetRepository;
use App\Enums\ContextAnswer;

beforeEach(function (): void {
    $this->ruleset = app(RulesetRepository::class)->active();
});

it('defaults every question to "not sure"', function (): void {
    expect(ContextProfile::defaults($this->ruleset)->toArray())->toBe([
        'consent_basis' => 'unsure',
        'specific_data' => 'unsure',
        'children' => 'unsure',
        'international_transfer' => 'unsure',
        'automated_decisions' => 'unsure',
    ]);
});

it('accepts answers as strings or enums and fills in the rest', function (): void {
    $profile = ContextProfile::fromInput($this->ruleset, [
        'children' => 'yes',
        'international_transfer' => ContextAnswer::No,
        'consent_basis' => '',
    ]);

    expect($profile->answer('children'))->toBe(ContextAnswer::Yes)
        ->and($profile->answer('international_transfer'))->toBe(ContextAnswer::No)
        ->and($profile->answer('consent_basis'))->toBe(ContextAnswer::Unsure);
});

it('rejects unknown questions and invalid answers', function (array $input): void {
    expect(fn () => ContextProfile::fromInput($this->ruleset, $input))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'unknown question' => [['planet' => 'yes']],
    'invalid answer' => [['children' => 'maybe']],
    'non-string answer' => [['children' => true]],
]);
