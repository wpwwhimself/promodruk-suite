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
    public function __construct(
        public string $supplier_name,
    )
    {
        $this->supplier_name = $supplier_name;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $sync_data = ProductSynchronization::where("supplier_name", $this->supplier_name)->first();
        if (!$sync_data->product_import_enabled && !$sync_data->stock_import_enabled) {
            return;
        }

        Log::info($this->supplier_name."> Synchronization started");

        $lock = "synch_".strtolower($this->supplier_name)."_in_progress";
        if (Cache::has($lock)) {
            Log::info($this->supplier_name."> - Stopped, it's locked");
            return;
        }

        Cache::put($lock, true, 60 * 30);

        try {
            Log::info($this->supplier_name."> - Initiating");

            $handlerName = "App\DataIntegrators\\" . $this->supplier_name . "Handler";
            $handler = new $handlerName();
            $handler->authenticate();
            $handler->downloadAndStoreAllProductData($sync_data);

            Log::info($this->supplier_name."> - Finished");
        } catch (\Exception $e) {
            Log::error($this->supplier_name."> - Error: " . $e->getMessage(), ["exception" => $e]);
        } finally {
            Cache::forget($lock);
        }

    }
}
