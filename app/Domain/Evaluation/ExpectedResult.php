<?php

declare(strict_types=1);

namespace App\Domain\Evaluation;

use App\Enums\RuleStatus;

/**
 * The expected outcome of one rule for one fixture. Genuinely borderline items
 * may list other acceptable statuses; the note explains why.
 */
final readonly class ExpectedResult
{
    /**
     * @param  list<RuleStatus>  $acceptable  alternatives also counted as correct
     */
    public function __construct(
        public string $ruleId,
        public bool $applicable,
        public ?RuleStatus $status,
        public array $acceptable,
        public ?string $note,
    ) {}

    public function accepts(RuleStatus $status): bool
    {
        return $this->applicable
            && ($status === $this->status || in_array($status, $this->acceptable, true));
    }
}
