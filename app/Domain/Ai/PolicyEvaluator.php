<?php

declare(strict_types=1);

namespace App\Domain\Ai;

/**
 * An AI provider (or the offline mock) that judges a batch of rules against a
 * sectioned policy. Providers stay thin: EvaluationService validates their
 * output, retries once and maps errors.
 */
interface PolicyEvaluator
{
    /**
     * Evaluates one batch of rules. Returns raw, unvalidated output.
     */
    public function evaluate(EvaluationRequest $request): ProviderResponse;

    /**
     * Short identifier stored with each analysis, e.g. "mock" or "claude".
     */
    public function name(): string;
}
