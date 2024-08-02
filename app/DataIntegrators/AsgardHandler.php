<?php

namespace App\DataIntegrators;

use App\Models\ProductSynchronization;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class AsgardHandler extends ApiHandler
{
    private const URL = "https://developers.bluecollection.eu/";
    private const SUPPLIER_NAME = "Asgard";
    public function getPrefix(): string { return "AS"; }

    public function authenticate(): void
    {
        function testRequest($url)
        {
            return Http::acceptJson()
                ->withToken(session("asgard_token"))
                ->get($url . "api/categories/1");
        }
        if (empty(session("asgard_token")))
            $this->prepareToken();

        $res = testRequest(self::URL);

        if ($res->unauthorized()) {
            $this->refreshToken();
            $res = testRequest(self::URL);
        }
        if ($res->unauthorized()) {
            $this->prepareToken();
        }
    }

    public function downloadAndStoreAllProductData(): void
    {
        ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["last_sync_started_at" => Carbon::now()]);

        $categories = Http::acceptJson()
            ->withToken(session("asgard_token"))
            ->get(self::URL . "api/categories")
            ->collect("results")
            ->mapWithKeys(fn ($el) => [$el["id"] => $el["pl"]]);
        $subcategories = Http::acceptJson()
            ->withToken(session("asgard_token"))
            ->get(self::URL . "api/subcategories")
            ->collect("results")
            ->mapWithKeys(fn ($el) => [$el["id"] => $el["pl"]]);

        $is_last_page = false;
        $page = 1;
        $counter = 0;
        $total = 0;

        while (!$is_last_page) {
            $res = Http::acceptJson()
                ->withToken(session("asgard_token"))
                ->get(self::URL . "api/products-index", [
                    "page" => $page++,
                ])
                ->collect();

            $total = $res["count"];

            foreach ($res["results"] as $product) {
                echo "- downloading product " . $product["index"] . "\n";

                $this->saveProduct(
                    $this->getPrefix() . $product["index"],
                    collect($product["names"])->firstWhere("language", "pl")["title"],
                    collect($product["descriptions"])->firstWhere("language", "pl")["text"],
                    $this->getPrefix() . Str::beforeLast($product["index"], "-"),
                    collect($product["image"])->sortBy("url")->map(fn ($el) => $el["url"])->toArray(),
                    implode(" > ", [$categories[$product["category"]], $subcategories[$product["subcategory"]]])
                );

                [$fd_amount, $fd_date] = $this->processFutureDelivery($product["future_delivery"]);

                $this->saveStock(
                    $this->getPrefix() . $product["index"],
                    $product["quantity"],
                    $fd_amount,
                    $fd_date
                );

                ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["progress" => (++$counter / $total) * 100]);
            }

            $is_last_page = $res["next"] == null;
        }
    }

    private function prepareToken()
    {
        $res = Http::acceptJson()
            ->post(self::URL . "api/token/", [
                "username" => env("ASGARD_API_LOGIN"),
                "password" => env("ASGARD_API_HASH_PASSWORD"),
            ]);
        session([
            "asgard_token" => $res->json("access"),
            "asgard_refresh_token" => $res->json("refresh"),
        ]);
    }

    private function refreshToken()
    {
        $res = Http::acceptJson()
            ->withToken(session("asgard_token"))
            ->post(self::URL . "api/token/refresh", [
                "refresh" => session("asgard_refresh_token"),
            ]);
        session("asgard_token", $res->json("access"));
    }

    private function processFutureDelivery(array $future_delivery) {
        if (count($future_delivery) == 0)
            return [null, null];

        // wybierz najbliższą dostawę
        $future_delivery = collect($future_delivery)
            ->sortBy("date")
            ->first();

        return [$future_delivery["quantity"], Carbon::parse($future_delivery["date"])];
    }
}
