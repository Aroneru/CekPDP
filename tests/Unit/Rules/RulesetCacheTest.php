<?php

declare(strict_types=1);

use App\Domain\Rules\RulesetCache;
use App\Domain\Rules\RulesetException;
use App\Domain\Rules\RulesetFactory;
use App\Domain\Rules\RulesetRepository;
use App\Domain\Rules\RulesetValidator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

beforeEach(function (): void {
    RulesetCache::flushMemory();

    $this->root = storage_path('framework/testing/ruleset-cache-'.Str::random(8));
    $this->rulesetPath = $this->root.'/uu-pdp-27-2022/1.0.0.yaml';
    File::ensureDirectoryExists(dirname($this->rulesetPath));
    File::copy(resource_path('rulesets/uu-pdp-27-2022/1.0.0.yaml'), $this->rulesetPath);

    $this->repository = fn (): RulesetRepository => new RulesetRepository(
        validator: app(RulesetValidator::class),
        factory: new RulesetFactory,
        directory: $this->root,
        rulesetId: 'uu-pdp-27-2022',
        activeVersion: '1.0.0',
        cache: new RulesetCache($this->root.'/cache', resource_path('rulesets/ruleset.schema.json')),
    );
});

afterEach(function (): void {
    File::deleteDirectory($this->root);
    RulesetCache::flushMemory();
});

it('stores validated data and serves it to later loads', function (): void {
    $first = ($this->repository)()->active();

    expect(glob($this->root.'/cache/*.json'))->toHaveCount(1);

    RulesetCache::flushMemory();

    expect(($this->repository)()->active())->toEqual($first);
});

it('validates an edited ruleset again instead of trusting the cache', function (): void {
    ($this->repository)()->active();

    File::put($this->rulesetPath, str_replace("\nversion: 1.0.0\n", "\nversion: 1.0.1\n", File::get($this->rulesetPath)));

    expect(fn () => ($this->repository)()->active())->toThrow(RulesetException::class);
});

it('falls back to parsing when a cache entry is unreadable', function (): void {
    $expected = ($this->repository)()->active();
    $entry = glob($this->root.'/cache/*.json')[0];

    File::put($entry, '{ not json');
    RulesetCache::flushMemory();

    expect(($this->repository)()->active())->toEqual($expected);
});
