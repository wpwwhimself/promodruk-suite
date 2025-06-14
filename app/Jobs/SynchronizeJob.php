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
        public ?string $single_module = null,
    )
    {
        $this->supplier_name = $supplier_name;
        $this->single_module = $single_module;
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

        $max_exec_time = 60 * 6; // defined by server

        if (Cache::has(self::getLockName("in_progress", $this->supplier_name, $this->single_module))) {
            $sync_data->addLog("stopped", 1, "🔒 Sync already in progress");
            return;
        }
        Cache::put(self::getLockName("in_progress", $this->supplier_name, $this->single_module), true, $max_exec_time * 3); // every 3 cycles

        if (Cache::has(self::getLockName("finished", $this->supplier_name, $this->single_module))) {
            $sync_data->addLog("stopped", 1, "🔒 Sync recently done, waiting for next cycle");
            return;
        }
        Cache::put(self::getLockName("finished", $this->supplier_name, $this->single_module), true, $max_exec_time * 10);

        try {
            $sync_data->addLog("in progress", 0, "Initiating");

            $handlerName = "App\DataIntegrators\\" . $this->supplier_name . "Handler";
            $handler = new $handlerName($sync_data, $this->single_module);
            $handler->authenticate();
            $handler->downloadAndStoreAllProductData();

            $sync_data->addLog("complete", 0, "Finished");
        } catch (\Exception $e) {
            $sync_data->addLog("error", 0, $e);
        } finally {
            Cache::forget(self::getLockName("in_progress", $this->supplier_name, $this->single_module));
            if ($this->single_module == "stock")
                Cache::forget(self::getLockName("finished", $this->supplier_name, $this->single_module)); // stock sync must work as frequently as possible
        }
    }

    #region helpers
    public static function getLockName(string $type, string $supplier_name, string $module)
    {
        return "synch_".strtolower($supplier_name)."_".$type."_".$module;
    }
    #endregion
}
