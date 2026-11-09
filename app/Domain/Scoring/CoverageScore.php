<?php

declare(strict_types=1);

namespace App\Domain\Scoring;

use App\Enums\WithheldReason;

/**
 * Policy Coverage: the share of applicable legal requirements the policy
 * discloses. It measures disclosures, not compliance.
 *
 *     coverage = (Addressed + 0.5 × Partially addressed) ÷ (Addressed + Partially addressed + Not found)
 *
 * Every rule has equal weight. Rules that need human review are left out of
 * the formula, and when they are more than REVIEW_THRESHOLD_PERCENT of the
 * applicable legal requirements no figure is given at all. Best practices are
 * reported separately and never affect the figure.
 */
final readonly class CoverageScore
{
    public const FORMULA = 'coverage-v1';

    public const REVIEW_THRESHOLD_PERCENT = 20;

    public function __construct(
        public StatusCounts $legal,
        public StatusCounts $bestPractice,
        public ?int $percent,
        public ?WithheldReason $withheld,
        public int $reviewThresholdPercent = self::REVIEW_THRESHOLD_PERCENT,
    ) {}

    public static function calculate(StatusCounts $legal, StatusCounts $bestPractice, int $reviewThresholdPercent = self::REVIEW_THRESHOLD_PERCENT): self
    {
        $withheld = match (true) {
            $legal->total() === 0 => WithheldReason::NoLegalRequirements,
            $legal->needsReview * 100 > $reviewThresholdPercent * $legal->total() => WithheldReason::TooManyReviews,
            default => null,
        };

        return new self(
            legal: $legal,
            bestPractice: $bestPractice,
            percent: $withheld === null ? self::roundedPercent($legal) : null,
            withheld: $withheld,
            reviewThresholdPercent: $reviewThresholdPercent,
        );
    }

    public function isWithheld(): bool
    {
        return $this->withheld !== null;
    }

    /**
     * Share of applicable legal requirements that need human review, rounded
     * to a whole percent.
     */
    public function reviewSharePercent(): int
    {
        $total = $this->legal->total();

        return $total === 0 ? 0 : intdiv(200 * $this->legal->needsReview + $total, 2 * $total);
    }

    /**
     * The calculation with the real numbers, e.g. "(12 + 0.5 × 3) ÷ 17 = 79%".
     */
    public function arithmetic(): ?string
    {
        if ($this->percent === null) {
            return null;
        }

        return sprintf(
            '(%d + 0.5 × %d) ÷ %d = %d%%',
            $this->legal->addressed,
            $this->legal->partiallyAddressed,
            $this->legal->evaluated(),
            $this->percent,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'formula' => self::FORMULA,
            'percent' => $this->percent,
            'withheld' => $this->withheld?->value,
            'review_threshold_percent' => $this->reviewThresholdPercent,
            'legal' => $this->legal->toArray(),
            'best_practice' => $this->bestPractice->toArray(),
        ];
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            legal: StatusCounts::fromArray(is_array($data['legal'] ?? null) ? $data['legal'] : []),
            bestPractice: StatusCounts::fromArray(is_array($data['best_practice'] ?? null) ? $data['best_practice'] : []),
            percent: is_int($data['percent'] ?? null) ? $data['percent'] : null,
            withheld: is_string($data['withheld'] ?? null) ? WithheldReason::tryFrom($data['withheld']) : null,
            reviewThresholdPercent: is_int($data['review_threshold_percent'] ?? null) ? $data['review_threshold_percent'] : self::REVIEW_THRESHOLD_PERCENT,
        );
    }

    /**
     * Rounds half up with integer arithmetic, so 12.5% shows as 13%.
     */
    private static function roundedPercent(StatusCounts $legal): int
    {
        $evaluated = $legal->evaluated();

        return intdiv(100 * (2 * $legal->addressed + $legal->partiallyAddressed) + $evaluated, 2 * $evaluated);
    }
}
