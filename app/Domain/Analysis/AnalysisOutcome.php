<?php

declare(strict_types=1);

namespace App\Domain\Analysis;

use App\Domain\Document\PolicyDocument;
use App\Domain\Scoring\CoverageScore;

final readonly class AnalysisOutcome
{
    /**
     * @param  list<RuleOutcome>  $rules  every active rule, in ruleset order
     * @param  list<string>  $models
     */
    public function __construct(
        public PolicyDocument $document,
        public array $rules,
        public CoverageScore $score,
        public string $provider,
        public array $models,
        public int $providerRequests,
        public int $retries,
    ) {}
}
