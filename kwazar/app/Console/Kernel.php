<?php

namespace App\Console;

use App\Jobs\CreateOfferFilesJob;
use App\Jobs\SynchronizeJob;
use App\Models\OfferFile;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command("backup:clean")->cron("0 0 * * *");
        $schedule->command("backup:run")->cron("15 0 * * *");

        $interval = OfferFile::WORKER_DELAY_MINUTES;
        $schedule->job(new CreateOfferFilesJob())->cron(
            env("APP_ENV") == "local"
                ? "* * * * *"
                : "*/$interval * * * *"
        );
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
