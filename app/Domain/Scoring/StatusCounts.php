<?php

declare(strict_types=1);

namespace App\Domain\Scoring;

use App\Enums\RuleStatus;

/**
 * How many applicable rules of one requirement type ended in each status.
 */
final readonly class StatusCounts
{
    public function __construct(
        public int $addressed = 0,
        public int $partiallyAddressed = 0,
        public int $notFound = 0,
        public int $needsReview = 0,
    ) {}

    public function with(RuleStatus $status): self
    {
        return new self(
            addressed: $this->addressed + ($status === RuleStatus::Addressed ? 1 : 0),
            partiallyAddressed: $this->partiallyAddressed + ($status === RuleStatus::PartiallyAddressed ? 1 : 0),
            notFound: $this->notFound + ($status === RuleStatus::NotFound ? 1 : 0),
            needsReview: $this->needsReview + ($status === RuleStatus::NeedsHumanReview ? 1 : 0),
        );
    }

    public function total(): int
    {
        return $this->addressed + $this->partiallyAddressed + $this->notFound + $this->needsReview;
    }

    /**
     * Rules with a definite status (the score's denominator).
     */
    public function evaluated(): int
    {
        return $this->addressed + $this->partiallyAddressed + $this->notFound;
    }

    /**
     * @return array{addressed: int, partially_addressed: int, not_found: int, needs_human_review: int}
     */
    public function toArray(): array
    {
        return [
            'addressed' => $this->addressed,
            'partially_addressed' => $this->partiallyAddressed,
            'not_found' => $this->notFound,
            'needs_human_review' => $this->needsReview,
        ];
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $count = static fn (string $key): int => is_int($data[$key] ?? null) ? $data[$key] : 0;

        return new self(
            addressed: $count('addressed'),
            partiallyAddressed: $count('partially_addressed'),
            notFound: $count('not_found'),
            needsReview: $count('needs_human_review'),
        );
    }
}
