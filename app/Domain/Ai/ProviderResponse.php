<?php

declare(strict_types=1);

namespace App\Domain\Ai;

/**
 * A provider's raw answer: the decoded JSON (anything, until validated) and the
 * model that actually produced it.
 */
final readonly class ProviderResponse
{
    /**
     * @param  array<string, int>  $usage  token counts, when the provider reports them
     */
    public function __construct(
        public mixed $payload,
        public string $model,
        public array $usage = [],
    ) {}
}
