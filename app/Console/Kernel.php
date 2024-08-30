<?php

namespace App\Console;

use App\Jobs\SynchronizeJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $integrators = [
            "Asgard",
            "Midocean",
            "Easygifts",
            "PAR",
            "Macma",
            "Axpol",
            "Anda",
        ];

        foreach ($integrators as $i => $integrator) {
            $schedule->job(new SynchronizeJob($integrator))
                ->cron(($i * 60 / count($integrators)) . " * * * *");
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
