<?php

declare(strict_types=1);

namespace App\Domain\Reporting;

use App\Domain\Document\LanguageProfile;
use App\Domain\Rules\Ruleset;
use App\Domain\Scoring\CoverageScore;
use App\Enums\RuleStatus;
use Carbon\CarbonImmutable;

/**
 * Everything the results page (and, later, the exports) shows for one
 * completed analysis.
 */
final readonly class Report
{
    /**
     * @param  list<ReportContextAnswer>  $context
     * @param  list<ReportItem>  $legal  applicable legal requirements
     * @param  list<ReportItem>  $bestPractices  applicable best practices
     * @param  list<ReportItem>  $notApplicable
     */
    public function __construct(
        public string $analysisId,
        public Ruleset $ruleset,
        public CoverageScore $score,
        public LanguageProfile $language,
        public int $characters,
        public int $words,
        public int $sections,
        public string $provider,
        public ?string $model,
        public ?CarbonImmutable $completedAt,
        public ?CarbonImmutable $expiresAt,
        public array $context,
        public array $legal,
        public array $bestPractices,
        public array $notApplicable,
    ) {}

    /**
     * Whether the results come from the offline keyword matcher rather than AI.
     */
    public function isOffline(): bool
    {
        return $this->provider === 'mock';
    }

    /**
     * @return list<ReportItem>
     */
    public function needsReview(): array
    {
        return array_values(array_filter(
            [...$this->legal, ...$this->bestPractices],
            static fn (ReportItem $item): bool => $item->status === RuleStatus::NeedsHumanReview,
        ));
    }
}
