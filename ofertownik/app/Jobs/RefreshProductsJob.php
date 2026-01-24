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
use Illuminate\Support\Facades\Storage;

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

        $this->log("Refreshing products started");

        $this->log("Contacting Magazyn...");
        $status = $this->status([
            "status" => "pobieranie",
            "last_sync_started_at" => now(),
            "last_sync_zero_at" => empty($status["current_id"]) ? now() : $status["last_sync_zero_at"],
        ]);

        $products_starting = Product::select("product_family_id")
            ->distinct()
            ->get()
            ->pluck("product_family_id");
        $counter = 0;
        $total = count($products_starting);

        try {
            foreach ($products_starting->chunk(200) as $i => $product_batch) {
                $products = [];
                $missing = [];

                $this->log("Starting batch $i");
                [
                    "products" => $products,
                    "missing" => $missing,
                ] = Http::timeout(120)
                    ->post(env("MAGAZYN_API_URL") . "products/for-refresh", [
                        "ids" => $product_batch,
                        "families" => true,
                    ])->collect();

                $status = $this->status([
                    "status" => "przetwarzanie",
                    "progress" => 0,
                ]);

                foreach (collect($products)->sortBy("id") as $family) {
                    $counter++;
                    if (($status["current_id"] ?? null) > $family["id"]) continue;

                    $status = $this->status([
                        "current_id" => $family["id"],
                        "progress" => round($counter / $total * 100),
                    ]);

                    $updated_ids = [];
                    foreach ($family["products"] ?? [] as $product) {
                        $product = Product::updateOrCreate(["id" => $product["id"]], [
                            "product_family_id" => $product["product_family_id"],
                            "front_id" => $product["front_id"],
                            "name" => $product["name"],
                            "subtitle" => $product["product_family"]["subtitle"],
                            "family_name" => $product["product_family"]["name"],
                            "query_string" => implode(" ", [
                                $product["front_id"],
                                $product["name"],
                                $product["variant_data"]["name"] ?? null,
                            ]),
                            "description" => $product["combined_description"] ?? null,
                            "specification" => $product["specification"] ?? null,
                            "description_label" => $product["product_family"]["description_label"],
                            "images" => $product["combined_images"] ?? null,
                            "thumbnails" => $product["combined_thumbnails"] ?? null,
                            "color" => $product["variant_data"],
                            "sizes" => $product["sizes"],
                            "extra_filtrables" => $product["extra_filtrables"],
                            "brand_logo" => $product["brand_logo"],
                            "original_sku" => $product["original_sku"],
                            "price" => $product["show_price"] ? ($product["price"] * ($product["ofertownik_price_multiplier"] ?? 1)) : null,
                            "tabs" => $product["combined_tabs"] ?? null,
                            "is_synced_with_magazyn" => true,
                        ]);
                        $updated_ids[] = $product->id;
                    }

                    // "delete" missing product variants
                    Product::whereNotIn("id", $updated_ids)
                        ->where("product_family_id", $family["id"])
                        ->update([
                            "is_synced_with_magazyn" => false,
                        ]);
                }

                $this->log("Mid batch cleanup...", "info", ["missing_count" => count($missing)]);

                if (count($missing) > 0) {
                    Product::whereIn("product_family_id", $missing)
                        ->update([
                            "is_synced_with_magazyn" => false,
                        ]);
                }
            }

            $status = $this->status([
                "status" => "gotowe",
                "current_id" => null,
                "progress" => 100,
                "last_sync_completed_at" => now(),
                "last_sync_zero_to_full" => now()->diffInSeconds($status["last_sync_zero_at"] ?? now()),
            ]);

            $this->log("Done!");
        } catch (\Throwable $th) {
            $this->log($th->getMessage(), "error", $th->getTrace());
            $status = $this->status([
                "status" => "błąd 🟥",
            ]);
        }
    }

    private function log($message, $lvl = "info", $context = [])
    {
        Log::$lvl("🍃 $message", $context);
    }

    public static function status($new = null)
    {
        $setting = Storage::disk("public")->get("meta/refresh-products-status.json");
        $old = json_decode($setting, true);

        if ($new) {
            $new = [...$old, ...$new];
            Storage::disk("public")->put(
                "meta/refresh-products-status.json",
                json_encode($new)
            );
            return $new;
        }

        return $old;
    }
}
