<?php

namespace App\Console;

use App\Jobs\SynchronizeJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    // list used to automatically add product_synchronizations row if one is missing
    public const INTEGRATORS = [
        "Asgard",
        "Midocean",
        "Easygifts",
        "PAR",
        "Macma",
        "Axpol",
        "Anda",
        "Maxim",
        "FalkRoss",
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        foreach (self::INTEGRATORS as $i => $integrator) {
            $schedule->job(new SynchronizeJob($integrator))
                ->cron(
                    env("APP_ENV") == "local"
                    ? "* * * * *"
                    : "*/10 * * * *"
                        // round($i * 60 / count(self::INTEGRATORS)) . " * * * *"
                );
        }

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
