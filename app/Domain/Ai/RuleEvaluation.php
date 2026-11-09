<?php

declare(strict_types=1);

namespace App\Domain\Ai;

use App\Domain\Rules\LocalizedText;
use App\Enums\EvaluationSource;
use App\Enums\RuleStatus;

/**
 * A validated, but not yet evidence-checked, result for one rule.
 */
final readonly class RuleEvaluation
{
    /**
     * @param  list<EvidenceQuote>  $evidence
     */
    public function __construct(
        public string $ruleId,
        public RuleStatus $status,
        public LocalizedText $reason,
        public array $evidence,
        public EvaluationSource $source = EvaluationSource::Provider,
    ) {}
}
