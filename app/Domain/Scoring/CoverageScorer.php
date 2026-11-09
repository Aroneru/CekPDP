<?php

declare(strict_types=1);

namespace App\Domain\Scoring;

use App\Domain\Analysis\RuleOutcome;
use App\Enums\RequirementType;

/**
 * Counts final statuses per requirement type and calculates Policy Coverage.
 * Deterministic: the same results always give the same score.
 */
final class CoverageScorer
{
    /**
     * @param  iterable<RuleOutcome>  $outcomes
     */
    public function score(iterable $outcomes): CoverageScore
    {
        $legal = new StatusCounts;
        $bestPractice = new StatusCounts;

        foreach ($outcomes as $outcome) {
            $status = $outcome->status();

            if (! $outcome->resolved->applicable || $status === null) {
                continue;
            }

            if ($outcome->resolved->requirementType === RequirementType::Legal) {
                $legal = $legal->with($status);
            } else {
                $bestPractice = $bestPractice->with($status);
            }
        }

        return CoverageScore::calculate($legal, $bestPractice);
    }
}
