<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

// Policy text and results are deleted as soon as their retention period ends.
Schedule::command('cekpdp:purge-expired')->everyMinute()->withoutOverlapping();
