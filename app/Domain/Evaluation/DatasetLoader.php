<?php

declare(strict_types=1);

namespace App\Domain\Evaluation;

use App\Domain\Rules\ApplicabilityResolver;
use App\Domain\Rules\ContextProfile;
use App\Domain\Rules\ResolvedRule;
use App\Domain\Rules\Ruleset;
use App\Enums\RuleStatus;
use InvalidArgumentException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Loads evaluation/expected/{slug}.yaml with its policy in
 * evaluation/fixtures/{slug}.md, and checks the expectations against the
 * ruleset: every active rule must have an expectation, and rules that don't
 * apply under the fixture's context answers must be marked not_applicable.
 */
final class DatasetLoader
{
    private const NOT_APPLICABLE = 'not_applicable';

    private const LANGUAGES = ['id', 'en', 'mixed'];

    public function __construct(
        private readonly string $directory,
        private readonly ApplicabilityResolver $resolver,
    ) {}

    /**
     * @return list<DatasetCase>
     *
     * @throws DatasetException with every problem found
     */
    public function load(Ruleset $ruleset): array
    {
        $paths = glob($this->directory.DIRECTORY_SEPARATOR.'expected'.DIRECTORY_SEPARATOR.'*.yaml') ?: [];
        sort($paths);

        if ($paths === []) {
            throw new DatasetException(["no expectation files in {$this->directory}/expected"]);
        }

        $cases = [];
        $errors = [];

        foreach ($paths as $path) {
            $slug = basename($path, '.yaml');

            try {
                $cases[] = $this->loadCase($ruleset, $slug, $path);
            } catch (DatasetException $exception) {
                array_push($errors, ...array_map(static fn (string $error): string => "{$slug}: {$error}", $exception->errors));
            }
        }

        if ($errors !== []) {
            throw new DatasetException($errors);
        }

        return $cases;
    }

    private function loadCase(Ruleset $ruleset, string $slug, string $path): DatasetCase
    {
        $data = $this->parse($path);
        $errors = [];

        $fixturePath = $this->directory.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.$slug.'.md';

        if (! is_file($fixturePath) || trim((string) file_get_contents($fixturePath)) === '') {
            $errors[] = "fixture file missing or empty: fixtures/{$slug}.md";
        }

        if (($data['fixture'] ?? null) !== $slug) {
            $errors[] = 'fixture must equal the file name';
        }

        if (($data['ruleset_version'] ?? null) !== $ruleset->version) {
            $errors[] = "ruleset_version must be {$ruleset->version}";
        }

        $language = $data['language'] ?? null;

        if (! in_array($language, self::LANGUAGES, true)) {
            $errors[] = 'language must be one of: '.implode(', ', self::LANGUAGES);
        }

        $title = $this->text($data, 'title');
        $description = $this->text($data, 'description');

        if ($title === null || $description === null) {
            $errors[] = 'title and description are required';
        }

        try {
            $context = ContextProfile::fromInput($ruleset, is_array($data['context'] ?? null) ? $data['context'] : []);
        } catch (InvalidArgumentException $exception) {
            throw new DatasetException([...$errors, $exception->getMessage()]);
        }

        $expectedData = is_array($data['expected'] ?? null) ? $data['expected'] : [];
        $expected = [];

        foreach ($this->resolver->resolve($ruleset, $context) as $rule) {
            $ruleId = $rule->rule->id;

            if (! array_key_exists($ruleId, $expectedData)) {
                $errors[] = "{$ruleId}: no expected result";

                continue;
            }

            $result = $this->expectedResult($ruleId, $expectedData[$ruleId], $rule, $errors);

            if ($result !== null) {
                $expected[$ruleId] = $result;
            }
        }

        foreach (array_keys($expectedData) as $ruleId) {
            if (! $ruleset->hasRule((string) $ruleId) || ! $ruleset->rule((string) $ruleId)->isActive()) {
                $errors[] = "{$ruleId}: not an active rule in ruleset {$ruleset->version}";
            }
        }

        if ($errors !== []) {
            throw new DatasetException($errors);
        }

        return new DatasetCase(
            slug: $slug,
            title: (string) $title,
            language: (string) $language,
            description: (string) $description,
            fixturePath: $fixturePath,
            context: $context,
            expected: $expected,
        );
    }

    /**
     * @param  list<string>  $errors  appended to when the entry is invalid
     */
    private function expectedResult(string $ruleId, mixed $value, ResolvedRule $rule, array &$errors): ?ExpectedResult
    {
        /** @var array<array-key, mixed> $entry */
        $entry = is_array($value) ? $value : ['status' => $value];
        $statusValue = $entry['status'] ?? null;
        $note = $this->text($entry, 'note');

        if ($statusValue === self::NOT_APPLICABLE) {
            if ($rule->applicable) {
                $errors[] = "{$ruleId}: marked not_applicable but the rule applies under this fixture's context";

                return null;
            }

            return new ExpectedResult($ruleId, false, null, [], $note);
        }

        if (! $rule->applicable) {
            $errors[] = "{$ruleId}: must be not_applicable under this fixture's context";

            return null;
        }

        $status = is_string($statusValue) ? RuleStatus::tryFrom($statusValue) : null;

        if ($status === null) {
            $errors[] = "{$ruleId}: unknown status ".(is_scalar($statusValue) ? (string) $statusValue : '?');

            return null;
        }

        $acceptable = [];

        foreach ((array) ($entry['acceptable'] ?? []) as $alternative) {
            $alternativeStatus = is_string($alternative) ? RuleStatus::tryFrom($alternative) : null;

            if ($alternativeStatus === null || $alternativeStatus === $status) {
                $errors[] = "{$ruleId}: invalid acceptable status";

                continue;
            }

            $acceptable[] = $alternativeStatus;
        }

        if ($acceptable !== [] && $note === null) {
            $errors[] = "{$ruleId}: acceptable alternatives need a note explaining why";
        }

        return new ExpectedResult($ruleId, true, $status, $acceptable, $note);
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    private function text(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function parse(string $path): array
    {
        try {
            $data = Yaml::parseFile($path);
        } catch (ParseException $exception) {
            throw new DatasetException(['invalid YAML: '.$exception->getMessage()]);
        }

        if (! is_array($data)) {
            throw new DatasetException(['the file does not contain a mapping']);
        }

        /** @var array<string, mixed> $data */
        return $data;
    }
}
