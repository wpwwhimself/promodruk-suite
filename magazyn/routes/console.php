<?php

use App\Jobs\SynchronizeJob;
use App\Models\ProductSynchronization;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

ProductSynchronization::queue()
    ->filter(fn ($sync) => $sync["queue"]->enabled != 0)
    ->each(function ($sync) {
        Schedule::job(new SynchronizeJob($sync["sync"]->supplier_name, $sync["queue"]->module))
            ->cron(env("APP_ENV") == "local"
                ? "* * * * *"
                : ("*/" . env("SYNC_INTERVAL", 15) . " * * * *")
            );
    });
