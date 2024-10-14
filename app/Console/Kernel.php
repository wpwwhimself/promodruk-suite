<?php

namespace App\Console;

use App\Jobs\CleanQueryFilesJob;
use App\Jobs\RefreshProductsJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->job(new RefreshProductsJob)->cron(
            env("APP_ENV") == "local"
            ? "* * * * *"
            : "0 * * * *"
        );
        $schedule->job(new CleanQueryFilesJob)->hourly();

        $schedule->command("backup:clean")->cron("0 0 * * *");
        $schedule->command("backup:run")->cron("15 0 * * *");
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
