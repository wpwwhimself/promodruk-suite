<?php

namespace App\DataIntegrators;

use App\Models\ProductSynchronization;
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

            $total = $res->count;

            foreach ($res->results as $product) {
                $this->saveProduct(
                    $this->getPrefix() . $product["index"],
                    collect($product["names"])->firstWhere("language", "pl")["title"],
                    collect($product["descriptions"])->firstWhere("language", "pl")["text"],
                    $this->getPrefix() . Str::beforeLast($product["index"], "-"),
                    collect($product["image"])->sortBy("url")->map(fn ($el) => $el["url"])->toArray(),
                );

                ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["progress" => (++$counter / $total) * 100]);
            }

            $is_last_page = $res->next !== null;
        }
    }

    public function getData(string $params = null): Collection
    {
        $prefix = substr($params, 0, strlen($this->getPrefix()));
        if ($prefix == $this->getPrefix()) $params = substr($params, strlen($this->getPrefix()));

        if (empty(session("asgard_token")))
            $this->prepareToken();

        $res = $this->getStockInfo($params);

        if ($res->unauthorized()) {
            $this->refreshToken();
            $res = $this->getStockInfo($params);
        }
        if ($res->unauthorized()) {
            $this->prepareToken();
            $res = $this->getStockInfo($params);
        }

        return $res->collect("results")
            ->map(fn($i) => [
                "code" => $this->getPrefix() . $i["index"],
                "name" => collect($i["names"])->first(fn ($el) => $el["language"] == "pl")["title"],
                "description" => collect($i["descriptions"])->first(fn ($el) => $el["language"] == "pl")["text"],
                "image_url" => collect($i["image"])->sortBy("url")->map(fn ($el) => $el["url"]),
                "variant_name" => collect($i["additional"])->first(fn ($el) => $el["item"] == "color_product")["value"],
                "quantity" => $i["quantity"],
                "future_delivery" => $this->processFutureDelivery($i["future_delivery"]),
            ]);
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

    private function getStockInfo(string $query = null)
    {
        return Http::acceptJson()
            ->withToken(session("asgard_token"))
            ->get(self::URL . "api/products-index", [
                "search" => $query,
            ]);
    }

    private function processFutureDelivery(array $future_delivery) {
        if (count($future_delivery) == 0)
            return "brak";

        // wybierz najbliższą dostawę
        $future_delivery = collect($future_delivery)
            ->sortBy("date")
            ->first();

        return $future_delivery["quantity"] . " szt., ok. " . $future_delivery["date"];
    }
}
