<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AnalysisPurger;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('cekpdp:purge-expired')]
#[Description('Delete analyses (policy text and results) whose retention period has ended')]
final class PurgeExpiredAnalysesCommand extends Command
{
    public function handle(AnalysisPurger $purger): int
    {
        $deleted = $purger->purgeExpired();

        $this->info($deleted === 1 ? 'Deleted 1 expired analysis.' : "Deleted {$deleted} expired analyses.");

        return self::SUCCESS;
    }
}
