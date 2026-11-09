<?php

declare(strict_types=1);

namespace App\Domain\Rules;

use App\Enums\LegalMapping;
use App\Enums\RequirementType;
use App\Enums\RuleEvaluator;
use App\Enums\RuleReviewStatus;

/**
 * One checklist item: what the policy should say, why, and how to judge it.
 */
final readonly class Rule
{
    /**
     * @param  list<RuleReference>  $references
     * @param  list<string>  $typeOverrides  keys into Ruleset::$overrides
     */
    public function __construct(
        public string $id,
        public RuleReviewStatus $status,
        public string $category,
        public RequirementType $requirementType,
        public ?LegalMapping $legalMapping,
        public ?LocalizedText $interpretationNote,
        public LocalizedText $title,
        public LocalizedText $explanation,
        public array $references,
        public Applicability $applicability,
        public array $typeOverrides,
        public LocalizedList $lookFor,
        public RuleCriteria $criteria,
        public LocalizedText $nextStep,
        public LocalizedText $exampleWording,
        public RuleEvaluator $evaluator,
        public RuleSignals $signals,
    ) {}

    public function isActive(): bool
    {
        return $this->status === RuleReviewStatus::Active;
    }

    public function isInterpretive(): bool
    {
        return $this->legalMapping === LegalMapping::Interpretive;
    }

    /**
     * @return list<RuleReference>
     */
    public function basisReferences(): array
    {
        return array_values(array_filter(
            $this->references,
            static fn (RuleReference $reference): bool => $reference->isBasis(),
        ));
    }
}
