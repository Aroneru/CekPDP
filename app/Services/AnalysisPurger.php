<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Analysis;
use App\Models\RuleResult;
use Carbon\CarbonInterface;

/**
 * Deletes analyses with their policy text and results. Results are deleted
 * explicitly as well as by the foreign-key cascade, so nothing is left behind
 * even where foreign keys are not enforced.
 */
final class AnalysisPurger
{
    private const CHUNK = 500;

    /**
     * Deletes every analysis whose retention period has ended.
     *
     * @return int the number of analyses deleted
     */
    public function purgeExpired(?CarbonInterface $now = null): int
    {
        $deleted = 0;

        do {
            /** @var list<string> $ids */
            $ids = Analysis::query()->expired($now)->limit(self::CHUNK)->pluck('id')->all();

            if ($ids !== []) {
                $deleted += $this->deleteIds($ids);
            }
        } while (count($ids) === self::CHUNK);

        return $deleted;
    }

    public function delete(Analysis $analysis): void
    {
        $this->deleteIds([$analysis->id]);
    }

    /**
     * @param  list<string>  $ids
     */
    private function deleteIds(array $ids): int
    {
        return (new Analysis)->getConnection()->transaction(static function () use ($ids): int {
            RuleResult::query()->whereIn('analysis_id', $ids)->delete();

            return Analysis::query()->whereKey($ids)->delete();
        });
    }
}
