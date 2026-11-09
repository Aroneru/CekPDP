<?php

declare(strict_types=1);

namespace App\Domain\Ai;

use App\Domain\Rules\LocalizedText;
use App\Enums\RuleStatus;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Helper;
use Opis\JsonSchema\Validator;

/**
 * Checks provider output against resources/schemas/evaluation-response.schema.json,
 * then against the request: every requested rule exactly once, nothing else,
 * and no evidence on Not found results.
 */
final class ResponseValidator
{
    private ?object $schema = null;

    public function __construct(
        private readonly string $schemaPath,
    ) {}

    /**
     * @return list<RuleEvaluation> in the order the rules were requested
     *
     * @throws InvalidProviderOutput
     */
    public function validate(mixed $payload, EvaluationRequest $request): array
    {
        $errors = $this->schemaErrors($payload);

        if ($errors !== []) {
            throw new InvalidProviderOutput($errors);
        }

        $requested = $request->ruleIds();
        $evaluations = [];

        // The schema has already guaranteed these shapes; reading them with
        // explicit checks keeps the types honest.
        foreach ($this->results($payload) as $index => $result) {
            $ruleId = $this->string($result, 'rule_id');
            $status = RuleStatus::tryFrom($this->string($result, 'status'));
            $evidence = $this->evidence($result);

            if (! in_array($ruleId, $requested, true)) {
                $errors[] = "results[{$index}]: rule {$ruleId} was not requested";

                continue;
            }

            if (isset($evaluations[$ruleId])) {
                $errors[] = "results[{$index}]: rule {$ruleId} appears more than once";

                continue;
            }

            if ($status === null) {
                $errors[] = "results[{$index}]: unknown status";

                continue;
            }

            if ($status === RuleStatus::NotFound && $evidence !== []) {
                $errors[] = "results[{$index}]: a not_found result must not quote evidence";
            }

            $evaluations[$ruleId] = new RuleEvaluation(
                ruleId: $ruleId,
                status: $status,
                reason: new LocalizedText($this->string($result, 'reason_id'), $this->string($result, 'reason_en')),
                evidence: $evidence,
            );
        }

        foreach ($requested as $ruleId) {
            if (! isset($evaluations[$ruleId])) {
                $errors[] = "rule {$ruleId} is missing from the results";
            }
        }

        if ($errors !== []) {
            throw new InvalidProviderOutput($errors);
        }

        return array_map(static fn (string $ruleId): RuleEvaluation => $evaluations[$ruleId], $request->ruleIds());
    }

    /**
     * @return list<array<array-key, mixed>>
     */
    private function results(mixed $payload): array
    {
        $results = is_array($payload) && is_array($payload['results'] ?? null) ? $payload['results'] : [];

        return array_values(array_filter($results, is_array(...)));
    }

    /**
     * @param  array<array-key, mixed>  $result
     * @return list<EvidenceQuote>
     */
    private function evidence(array $result): array
    {
        $quotes = [];

        foreach (is_array($result['evidence'] ?? null) ? $result['evidence'] : [] as $quote) {
            if (is_array($quote)) {
                $quotes[] = new EvidenceQuote($this->string($quote, 'section_id'), $this->string($quote, 'quote'));
            }
        }

        return $quotes;
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    private function string(array $data, string $key): string
    {
        return is_string($data[$key] ?? null) ? $data[$key] : '';
    }

    /**
     * @return list<string>
     */
    private function schemaErrors(mixed $payload): array
    {
        $validator = new Validator;
        $validator->setMaxErrors(25);

        $error = $validator->validate(Helper::toJSON($payload), $this->schema())->error();

        if ($error === null) {
            return [];
        }

        $errors = [];

        foreach ((new ErrorFormatter)->format($error, false) as $path => $message) {
            $errors[] = sprintf('schema %s: %s', $path === '' ? '/' : $path, is_string($message) ? $message : 'invalid');
        }

        return $errors;
    }

    private function schema(): object
    {
        if ($this->schema === null) {
            $schema = json_decode((string) file_get_contents($this->schemaPath), false, flags: JSON_THROW_ON_ERROR);
            $this->schema = is_object($schema) ? $schema : throw new \RuntimeException('The evaluation response schema is not a JSON object.');
        }

        return $this->schema;
    }
}
