<?php

declare(strict_types=1);

arch('application code declares strict types')
    ->expect('App')
    ->toUseStrictTypes();

arch('no debugging calls are left behind')
    ->expect(['dd', 'dump', 'ddd', 'ray', 'var_dump', 'print_r', 'var_export'])
    ->not->toBeUsed();

arch('the domain layer does not depend on HTTP, Livewire or facades')
    ->expect('App\Domain')
    ->not->toUse(['Illuminate\Http', 'Livewire', 'Illuminate\Support\Facades']);

arch('enums are enums')
    ->expect('App\Enums')
    ->toBeEnums();

arch('domain value objects are immutable')
    ->expect([
        'App\Domain\Rules\Rule',
        'App\Domain\Rules\Ruleset',
        'App\Domain\Rules\Provision',
        'App\Domain\Rules\LocalizedText',
        'App\Domain\Rules\ContextProfile',
        'App\Domain\Evaluation\ExpectedResult',
        'App\Domain\Evaluation\SamplePolicy',
        'App\Domain\Document\PolicyDocument',
        'App\Domain\Document\Section',
        'App\Domain\Document\TextUnit',
        'App\Domain\Document\LanguageProfile',
        'App\Domain\Ai\EvaluationRequest',
        'App\Domain\Ai\ProviderResponse',
        'App\Domain\Ai\RuleEvaluation',
        'App\Domain\Ai\EvidenceQuote',
        'App\Domain\Ai\EvaluationOutcome',
        'App\Domain\Evidence\VerifiedQuote',
        'App\Domain\Evidence\VerifiedEvaluation',
        'App\Domain\Evidence\VerificationSummary',
        'App\Domain\Scoring\CoverageScore',
        'App\Domain\Scoring\StatusCounts',
        'App\Domain\Analysis\AnalysisOutcome',
        'App\Domain\Analysis\RuleOutcome',
        'App\Domain\Reporting\Report',
        'App\Domain\Reporting\ReportItem',
        'App\Domain\Reporting\ReportReference',
    ])
    ->toBeReadonly();

arch('AI providers implement the provider contract')
    ->expect('App\Domain\Ai\MockEvaluator')
    ->toImplement('App\Domain\Ai\PolicyEvaluator');

arch('queued jobs carry identifiers, not models or policy text')
    ->expect('App\Jobs')
    ->not->toUse(['App\Models\Analysis', 'App\Domain\Document\PolicyDocument']);
