<?php

namespace App\Console;

use App\Jobs\SynchronizeJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    public const INTEGRATORS = [
        "Asgard",
        "Midocean",
        "Easygifts",
        "PAR",
        "Macma",
        "Axpol",
        "Anda",
        "Maxim",
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        foreach (self::INTEGRATORS as $i => $integrator) {
            $schedule->job(new SynchronizeJob($integrator))
                ->cron(
                    in_array($integrator, ["Macma"])
                    ? "0 * * * *"
                    : "*/10 * * * *"
                    // round($i * 60 / count(self::INTEGRATORS)) . " * * * *"
                );
        }
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
