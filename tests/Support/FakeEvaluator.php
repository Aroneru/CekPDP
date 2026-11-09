<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Ai\EvaluationRequest;
use App\Domain\Ai\PolicyEvaluator;
use App\Domain\Ai\ProviderResponse;
use App\Domain\Rules\Rule;
use Closure;

/**
 * A provider double that records requests and answers with whatever the test
 * supplies.
 */
final class FakeEvaluator implements PolicyEvaluator
{
    /** @var list<EvaluationRequest> */
    public array $requests = [];

    /**
     * @param  Closure(EvaluationRequest, int): mixed  $answer  receives the request and the call number (1-based)
     */
    public function __construct(
        private readonly Closure $answer,
        private readonly string $model = 'fake-model',
    ) {}

    /**
     * Answers every rule with the same status and no evidence.
     */
    public static function answering(string $status = 'not_found'): self
    {
        return new self(static fn (EvaluationRequest $request): array => self::payload($request, $status));
    }

    /**
     * @param  list<array{section_id: string, quote: string}>  $evidence
     * @return array{results: list<array<string, mixed>>}
     */
    public static function payload(EvaluationRequest $request, string $status = 'not_found', array $evidence = []): array
    {
        return ['results' => array_map(static fn (Rule $rule): array => [
            'rule_id' => $rule->id,
            'status' => $status,
            'reason_id' => 'Alasan uji.',
            'reason_en' => 'Test reason.',
            'evidence' => $evidence,
        ], $request->rules)];
    }

    public function evaluate(EvaluationRequest $request): ProviderResponse
    {
        $this->requests[] = $request;

        return new ProviderResponse(($this->answer)($request, count($this->requests)), $this->model);
    }

    public function name(): string
    {
        return 'fake';
    }
}
