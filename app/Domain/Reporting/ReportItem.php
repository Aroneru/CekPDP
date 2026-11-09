<?php

declare(strict_types=1);

namespace App\Domain\Reporting;

use App\Domain\Evidence\VerificationSummary;
use App\Domain\Evidence\VerifiedQuote;
use App\Domain\Rules\LocalizedText;
use App\Domain\Rules\Rule;
use App\Domain\Rules\TypeOverride;
use App\Enums\EvaluationSource;
use App\Enums\ReferenceRole;
use App\Enums\RequirementType;
use App\Enums\RuleStatus;

/**
 * One rule's result as shown in the report.
 */
final readonly class ReportItem
{
    /**
     * @param  list<VerifiedQuote>  $quotes
     * @param  list<ReportReference>  $references
     * @param  list<ReportContextAnswer>  $notApplicableBecause  the answers that made the rule not apply
     */
    public function __construct(
        public Rule $rule,
        public bool $applicable,
        public RequirementType $requirementType,
        public ?TypeOverride $override,
        public ?RuleStatus $status,
        public ?RuleStatus $modelStatus,
        public ?LocalizedText $reason,
        public array $quotes,
        public ?VerificationSummary $verification,
        public ?EvaluationSource $source,
        public array $references,
        public array $notApplicableBecause,
    ) {}

    public function wasDowngraded(): bool
    {
        return $this->verification?->downgrade !== null;
    }

    /**
     * Whether the policy could say more about this item (anything but Addressed).
     */
    public function hasGap(): bool
    {
        return $this->status !== null && $this->status !== RuleStatus::Addressed;
    }

    /**
     * @return list<ReportReference>
     */
    public function basisReferences(): array
    {
        return array_values(array_filter($this->references, static fn (ReportReference $reference): bool => $reference->role === ReferenceRole::Basis));
    }

    /**
     * @return list<ReportReference>
     */
    public function relatedReferences(): array
    {
        return array_values(array_filter($this->references, static fn (ReportReference $reference): bool => $reference->role !== ReferenceRole::Basis));
    }
}
