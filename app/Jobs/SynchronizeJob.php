<?php

namespace App\Jobs;

use App\Models\ProductSynchronization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class SynchronizeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $lock = "sync_job_in_progress";
        if (Cache::has($lock)) {
            echo("Job is already in progress");
            return;
        }

        Cache::put($lock, true, 3600);

        try {
            $synchronizations = ProductSynchronization::all();
            foreach ($synchronizations as $sync) {
                if (!$sync->product_import_enabled && !$sync->stock_import_enabled) continue;

                $handlerName = "App\DataIntegrators\\" . $sync->supplier_name . "Handler";

                $handler = new $handlerName();
                $handler->authenticate();
                $handler->downloadAndStoreAllProductData($sync);
            }
        } catch (\Exception $e) {
            echo($e->getMessage());
        } finally {
            Cache::forget($lock);
        }

    }
}
