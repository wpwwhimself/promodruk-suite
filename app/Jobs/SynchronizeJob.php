<?php

namespace App\Jobs;

use App\Models\ProductSynchronization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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
        Log::info("Synchronization job started");

        $lock = "sync_job_in_progress";
        if (Cache::has($lock)) {
            Log::info("- Stopped, already in progress");
            return;
        }

        Cache::put($lock, true, 3600);

        try {
            $synchronizations = ProductSynchronization::all();
            foreach ($synchronizations as $sync) {
                if (!$sync->product_import_enabled && !$sync->stock_import_enabled) continue;

                $handlerName = "App\DataIntegrators\\" . $sync->supplier_name . "Handler";
                Log::debug("- engaging $handlerName");

                $handler = new $handlerName();
                $handler->authenticate();
                $handler->downloadAndStoreAllProductData($sync);
            }
        } catch (\Exception $e) {
            Log::error("- Error in main loop: " . $e->getMessage());
        } finally {
            Cache::forget($lock);
        }

    }
}
