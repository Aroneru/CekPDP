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

arch('ruleset value objects are immutable')
    ->expect([
        'App\Domain\Rules\Rule',
        'App\Domain\Rules\Ruleset',
        'App\Domain\Rules\Provision',
        'App\Domain\Rules\LocalizedText',
        'App\Domain\Rules\ContextProfile',
        'App\Domain\Evaluation\ExpectedResult',
    ])
    ->toBeReadonly();
