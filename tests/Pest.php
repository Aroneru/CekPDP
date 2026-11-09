<?php

declare(strict_types=1);

use App\Domain\Document\DocumentBuilder;
use App\Domain\Document\PolicyDocument;
use App\Domain\Extraction\TextCleaner;
use App\Domain\Rules\Ruleset;
use App\Domain\Rules\RulesetRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

pest()->extend(TestCase::class)->in('Unit', 'Arch');
pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');

/**
 * The active ruleset as raw data, for tests that mutate it to check validation.
 *
 * @return array<string, mixed>
 */
function activeRulesetData(): array
{
    /** @var array<string, mixed> */
    return Yaml::parseFile(resource_path('rulesets/uu-pdp-27-2022/1.0.0.yaml'));
}

/**
 * Index of the rule with the given ID in raw ruleset data.
 *
 * @param  array<string, mixed>  $data
 */
function ruleIndex(array $data, string $id): int
{
    foreach ($data['rules'] as $index => $rule) {
        if ($rule['id'] === $id) {
            return $index;
        }
    }

    throw new InvalidArgumentException("No rule {$id}");
}

/**
 * Slugs of the evaluation fixtures. Usable in datasets (no app needed).
 *
 * @return list<string>
 */
function fixtureSlugs(): array
{
    $paths = glob(dirname(__DIR__).'/evaluation/fixtures/*.md') ?: [];
    sort($paths);

    return array_map(static fn (string $path): string => basename($path, '.md'), $paths);
}

function fixtureText(string $slug): string
{
    return (string) file_get_contents(dirname(__DIR__)."/evaluation/fixtures/{$slug}.md");
}

/**
 * The expected file of a fixture, as raw data.
 *
 * @return array<string, mixed>
 */
function fixtureExpectations(string $slug): array
{
    /** @var array<string, mixed> */
    return Yaml::parseFile(dirname(__DIR__)."/evaluation/expected/{$slug}.yaml");
}

function activeRuleset(): Ruleset
{
    return app(RulesetRepository::class)->active();
}

/**
 * A cleaned and structured policy document, as the pipeline sees it.
 */
function policyDocument(string $text): PolicyDocument
{
    return app(DocumentBuilder::class)->build(app(TextCleaner::class)->clean($text));
}
