<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RefreshProductsJob implements ShouldQueue
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
        $status = $this->status();

        if (!$status["enabled"]) {
            return;
        }

        $this->log("Refreshing products started");

        $this->log("Contacting Magazyn...");
        $status = $this->status([
            "status" => "pobieranie",
            "last_sync_started_at" => now(),
            "last_sync_zero_at" => empty($status["current_id"]) ? now() : $status["last_sync_zero_at"],
        ]);

        $missing_total = [];
        $products_starting = Product::select("product_family_id")
            ->distinct()
            ->get()
            ->pluck("product_family_id");
        $counter = 0;
        $total = count($products_starting);

        foreach ($products_starting->chunk(500) as $i => $product_batch) {
            $this->log("Starting batch $i");
            [
                "products" => $products,
                "missing" => $missing,
            ] = Http::post(env("MAGAZYN_API_URL") . "products/for-refresh", [
                "ids" => $product_batch,
                "families" => true,
            ])->collect();

            $status = $this->status([
                "status" => "przetwarzanie",
                "progress" => 0,
            ]);

            foreach (collect($products)->sortBy("id") as $family) {
                $counter++;
                if ($status["current_id"] > $family["id"]) continue;

                $status = $this->status([
                    "current_id" => $family["id"],
                    "progress" => round($counter / $total * 100),
                ]);

                $updated_ids = [];
                foreach ($family["products"] as $product) {
                    $product = Product::updateOrCreate(["id" => $product["id"]], [
                        "product_family_id" => $product["product_family_id"],
                        "front_id" => $product["front_id"],
                        "name" => $product["name"],
                        "family_name" => $product["product_family"]["name"],
                        "description" => $product["combined_description"] ?? null,
                        "description_label" => $product["product_family"]["description_label"],
                        "images" => $product["combined_images"] ?? null,
                        "thumbnails" => $product["combined_thumbnails"] ?? null,
                        "color" => $product["color"],
                        "sizes" => $product["sizes"],
                        "extra_filtrables" => $product["extra_filtrables"],
                        "brand_logo" => $product["brand_logo"],
                        "original_sku" => $product["original_sku"],
                        "price" => $product["price"],
                        "tabs" => $product["combined_tabs"] ?? null,
                    ]);
                    $updated_ids[] = $product->id;
                }

                // delete missing product variants
                Product::whereNotIn("id", $updated_ids)
                    ->where("product_family_id", $family["id"])
                    ->delete();
            }

            $missing_total = array_merge($missing_total, $missing);
        }

        $this->log("Refreshing products finished. Time to clean up...", "info", ["missing_count" => count($missing_total)]);
        $status = $this->status([
            "status" => "sprzątanie",
        ]);

        if (count($missing_total) > 0) {
            Product::whereIn("product_family_id", $missing_total)->delete();
        }

        $status = $this->status([
            "status" => "gotowe",
            "current_id" => null,
            "progress" => 100,
            "last_sync_completed_at" => now(),
            "last_sync_zero_to_full" => now()->diffInSeconds($status["last_sync_zero_at"]),
        ]);

        $this->log("Done!");
    }

    private function log($message, $lvl = "info", $context = [])
    {
        Log::$lvl("🍃 $message", $context);
    }

    public static function status($new = null)
    {
        $setting = Setting::find("product_refresh_status");
        $old = json_decode($setting->value, true);

        if ($new) {
            $setting->update(["value" => array_merge($old, $new)]);
        }

        return $old;
    }
}
