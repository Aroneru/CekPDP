<?php

declare(strict_types=1);

namespace App\Domain\Rules;

use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Helper;
use Opis\JsonSchema\Validator;

/**
 * Validates ruleset data in two passes:
 *  1. structure, against resources/rulesets/ruleset.schema.json;
 *  2. meaning: unique IDs, resolvable references, the legal/best-practice
 *     contract, valid context answers, compilable mock signals and dates.
 */
final class RulesetValidator
{
    public function __construct(
        private readonly string $schemaPath,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function validate(array $data): RulesetValidationResult
    {
        $schemaErrors = $this->schemaErrors($data);

        if ($schemaErrors !== []) {
            return new RulesetValidationResult($schemaErrors, []);
        }

        return $this->semanticChecks($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private function schemaErrors(array $data): array
    {
        $schema = json_decode((string) file_get_contents($this->schemaPath), false, flags: JSON_THROW_ON_ERROR);

        $validator = new Validator;
        $validator->setMaxErrors(25);

        $result = $validator->validate(Helper::toJSON($data), $schema);
        $error = $result->error();

        if ($error === null) {
            return [];
        }

        $errors = [];

        foreach ((new ErrorFormatter)->format($error, false) as $path => $message) {
            $errors[] = sprintf('schema %s: %s', $path === '' ? '/' : $path, is_string($message) ? $message : 'invalid');
        }

        return $errors;
    }

    /**
     * Runs after the schema passed, so the data has the expected shape.
     *
     * @param  array<string, mixed>  $data
     */
    private function semanticChecks(array $data): RulesetValidationResult
    {
        $errors = [];
        $warnings = [];

        /** @var array<string, array<string, mixed>> $sources */
        $sources = $data['sources'];
        /** @var array<string, array<string, mixed>> $contexts */
        $contexts = $data['contexts'];
        /** @var array<string, array<string, mixed>> $overrides */
        $overrides = $data['overrides'];
        /** @var array<string, array<string, mixed>> $provisions */
        $provisions = $data['provisions'];
        /** @var list<array<string, mixed>> $rules */
        $rules = $data['rules'];
        /** @var array<string, mixed> $instrument */
        $instrument = $data['instrument'];

        $lastReviewed = (string) $data['last_reviewed'];

        foreach ($contexts as $key => $context) {
            /** @var list<string> $options */
            $options = $context['options'];

            if (! in_array($context['default'], $options, true)) {
                $errors[] = "context {$key}: default answer is not one of its options";
            }
        }

        foreach ($overrides as $key => $override) {
            /** @var array<string, mixed> $when */
            $when = $override['when'];
            array_push($errors, ...$this->conditionErrors("override {$key}", $when, $contexts));
        }

        foreach ($provisions as $key => $provision) {
            $sourceKey = $this->stringify($provision['source']);

            if (! isset($sources[$sourceKey])) {
                $errors[] = "provision {$key}: unknown source {$sourceKey}";
            } elseif (($sources[$sourceKey]['kind'] ?? null) !== Source::KIND_OFFICIAL) {
                $errors[] = "provision {$key}: legal text must come from an official source";
            }

            if ((string) $provision['verified_on'] > $lastReviewed) {
                $errors[] = "provision {$key}: verified_on is after the ruleset's last_reviewed date";
            }
        }

        /** @var list<array<string, mixed>> $decisions */
        $decisions = $instrument['court_decisions'];

        foreach ($decisions as $decision) {
            $reference = $this->stringify($decision['ref']);
            $affects = $this->stringify($decision['affects']);
            $sourceKey = $this->stringify($decision['source']);

            if (! isset($provisions[$affects])) {
                $errors[] = "court decision {$reference}: unknown provision {$affects}";
            }

            if (! isset($sources[$sourceKey])) {
                $errors[] = "court decision {$reference}: unknown source {$sourceKey}";
            }
        }

        $seenIds = [];
        $referenced = [];

        foreach ($rules as $rule) {
            $id = $this->stringify($rule['id']);

            if (isset($seenIds[$id])) {
                $errors[] = "rule {$id}: duplicate rule ID";
            }

            $seenIds[$id] = true;

            array_push($errors, ...$this->ruleErrors($id, $rule, $provisions, $overrides, $contexts, $referenced));
        }

        foreach (array_keys($provisions) as $key) {
            if (! isset($referenced[$key])) {
                $warnings[] = "provision {$key}: not referenced by any rule";
            }
        }

        return new RulesetValidationResult($errors, $warnings);
    }

    /**
     * @param  array<string, mixed>  $rule
     * @param  array<string, array<string, mixed>>  $provisions
     * @param  array<string, array<string, mixed>>  $overrides
     * @param  array<string, array<string, mixed>>  $contexts
     * @param  array<string, true>  $referenced  collects referenced provision keys
     * @return list<string>
     */
    private function ruleErrors(string $id, array $rule, array $provisions, array $overrides, array $contexts, array &$referenced): array
    {
        $errors = [];
        $isLegal = $rule['requirement_type'] === 'legal';
        $hasBasis = false;

        /** @var list<array<string, mixed>> $references */
        $references = $rule['references'];

        foreach ($references as $reference) {
            $key = $this->stringify($reference['provision']);
            $referenced[$key] = true;

            if (! isset($provisions[$key])) {
                $errors[] = "rule {$id}: unknown provision {$key}";
            }

            if ($reference['role'] === 'basis') {
                $hasBasis = true;
            }
        }

        if ($isLegal && ! $hasBasis) {
            $errors[] = "rule {$id}: a legal requirement needs at least one basis reference";
        }

        if (! $isLegal && $hasBasis) {
            $errors[] = "rule {$id}: a best practice may only cite related provisions, not a basis";
        }

        /** @var list<string> $typeOverrides */
        $typeOverrides = $rule['type_overrides'] ?? [];

        foreach ($typeOverrides as $override) {
            if (! isset($overrides[$override])) {
                $errors[] = "rule {$id}: unknown type override {$override}";
            }

            if (! $isLegal) {
                $errors[] = "rule {$id}: type overrides only make sense for legal requirements";
            }
        }

        if (is_array($rule['applies_when'] ?? null)) {
            /** @var array<string, mixed> $appliesWhen */
            $appliesWhen = $rule['applies_when'];
            /** @var list<array<string, mixed>> $conditions */
            $conditions = $appliesWhen['any'];

            foreach ($conditions as $condition) {
                array_push($errors, ...$this->conditionErrors("rule {$id}", $condition, $contexts));
            }
        }

        /** @var array<string, mixed> $signals */
        $signals = $rule['signals'];

        foreach ($this->signalPatterns($signals) as $pattern) {
            if (@preg_match(self::signalRegex($pattern), '') === false) {
                $errors[] = "rule {$id}: signal pattern does not compile: {$pattern}";
            }
        }

        return $errors;
    }

    /**
     * Wraps a signal pattern in delimiters and flags for case-insensitive,
     * Unicode-aware matching. Shared with the mock provider.
     */
    public static function signalRegex(string $pattern): string
    {
        return '~'.str_replace('~', '\~', $pattern).'~iu';
    }

    /**
     * @param  array<string, mixed>  $condition
     * @param  array<string, array<string, mixed>>  $contexts
     * @return list<string>
     */
    private function conditionErrors(string $owner, array $condition, array $contexts): array
    {
        $context = $this->stringify($condition['context']);

        if (! isset($contexts[$context])) {
            return ["{$owner}: unknown context question {$context}"];
        }

        /** @var list<string> $options */
        $options = $contexts[$context]['options'];
        /** @var list<string> $answers */
        $answers = $condition['in'];
        $invalid = array_diff($answers, $options);

        return $invalid === []
            ? []
            : ["{$owner}: answer(s) ".implode(', ', $invalid)." not offered by context question {$context}"];
    }

    /**
     * @param  array<string, mixed>  $signals
     * @return list<string>
     */
    private function signalPatterns(array $signals): array
    {
        $patterns = [];
        /** @var list<array<string, list<string>>> $groups */
        $groups = [$signals['topic'], $signals['caps'], ...(array) $signals['complete']];

        foreach ($groups as $group) {
            foreach ($group as $languagePatterns) {
                foreach ($languagePatterns as $pattern) {
                    $patterns[] = $pattern;
                }
            }
        }

        return $patterns;
    }

    private function stringify(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '?';
    }
}
