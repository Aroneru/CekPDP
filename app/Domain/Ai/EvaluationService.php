<?php

declare(strict_types=1);

namespace App\Domain\Ai;

use App\Domain\Document\PolicyDocument;
use App\Domain\Rules\ResolvedRule;
use App\Enums\RuleEvaluator;

/**
 * Evaluates the applicable rules with the configured provider: language rules
 * are decided deterministically where possible, the rest go to the provider in
 * batches. Each batch's output is validated and, if invalid, requested once
 * more before the analysis fails.
 */
final class EvaluationService
{
    public function __construct(
        private readonly PolicyEvaluator $provider,
        private readonly ResponseValidator $validator,
        private readonly LanguageCheck $languageCheck,
        private readonly int $batchSize = 8,
    ) {}

    /**
     * @param  list<ResolvedRule>  $rules  the applicable rules
     * @param  (callable(int, int): void)|null  $onBatch  called with (batches done, batches total)
     *
     * @throws InvalidProviderOutput when a batch is still invalid after one retry
     */
    public function evaluate(PolicyDocument $document, array $rules, string $rulesetVersion, ?callable $onBatch = null): EvaluationOutcome
    {
        $decided = [];
        $forProvider = [];

        foreach ($rules as $resolved) {
            $rule = $resolved->rule;
            $decision = $rule->evaluator === RuleEvaluator::LanguageCheck ? $this->languageCheck->decide($rule, $document) : null;

            if ($decision !== null) {
                $decided[$rule->id] = $decision;
            } else {
                $forProvider[] = $rule;
            }
        }

        $batches = array_chunk($forProvider, max(1, $this->batchSize));
        $models = [];
        $requests = 0;
        $retries = 0;

        foreach ($batches as $done => $batch) {
            $request = new EvaluationRequest($document, $batch, $rulesetVersion);
            [$evaluations, $model, $attempts] = $this->evaluateBatch($request);

            foreach ($evaluations as $evaluation) {
                $decided[$evaluation->ruleId] = $evaluation;
            }

            $models[$model] = true;
            $requests += $attempts;
            $retries += $attempts - 1;

            if ($onBatch !== null) {
                $onBatch($done + 1, count($batches));
            }
        }

        $ordered = [];

        foreach ($rules as $resolved) {
            $ordered[$resolved->rule->id] = $decided[$resolved->rule->id];
        }

        return new EvaluationOutcome(
            evaluations: $ordered,
            provider: $this->provider->name(),
            models: array_keys($models),
            requests: $requests,
            retries: $retries,
        );
    }

    /**
     * @return array{list<RuleEvaluation>, string, int} evaluations, model, attempts
     */
    private function evaluateBatch(EvaluationRequest $request): array
    {
        try {
            $response = $this->provider->evaluate($request);

            return [$this->validator->validate($response->payload, $request), $response->model, 1];
        } catch (InvalidProviderOutput) {
            $retry = $request->retry();
            $response = $this->provider->evaluate($retry);

            return [$this->validator->validate($response->payload, $retry), $response->model, 2];
        }
    }
}
