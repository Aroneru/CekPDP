<?php

declare(strict_types=1);

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
