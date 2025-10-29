<?php

namespace App\Console;

use App\Jobs\RefreshMaterializedViews;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->job(new RefreshMaterializedViews())->everyFifteenMinutes();
        $schedule->job(new \App\Jobs\ProcessOutbox())->everyMinute();
        $schedule->job(new \App\Jobs\DispatchPendingWebhooks())->everyMinute();
    }
}
