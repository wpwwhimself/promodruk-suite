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
        $sync_data = ProductSynchronization::find($this->supplier_name);
        if (
            !$sync_data->product_import_enabled
            && !$sync_data->stock_import_enabled
            && !$sync_data->marking_import_enabled
        ) {
            return;
        }

        $lock = "synch_".strtolower($this->supplier_name)."_in_progress";
        if (Cache::has($lock)) {
            $sync_data->addLog("stopped", 1, "Sync already in progress");
            return;
        }

        Cache::put($lock, true, 60 * 12);

        try {
            $sync_data->addLog("in progress", 0, "Initiating");

            $handlerName = "App\DataIntegrators\\" . $this->supplier_name . "Handler";
            $handler = new $handlerName($sync_data);
            $handler->authenticate();
            $handler->downloadAndStoreAllProductData();

            $sync_data->addLog("complete", 0, "Finished");
        } catch (\Exception $e) {
            $sync_data->addLog("error", 0, $e);
        } finally {
            Cache::forget($lock);
        }

    }
}
