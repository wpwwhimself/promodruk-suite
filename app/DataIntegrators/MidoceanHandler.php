<?php

namespace App\DataIntegrators;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class MidoceanHandler extends ApiHandler
{
    private const URL = "https://api.midocean.com/gateway/";

    public function getData(string $params = null): Collection
    {
        $stock = $this->getStockInfo($params)
            ->map(fn($i) => [
                "code" => $i["sku"],
                "quantity" => $i["qty"],
                "future_delivery" => $this->processFutureDelivery($i),
            ])
            ->keyBy("code");

        $products = $this->getProductInfo($params);
        $products_names = $products->map(fn($i) => $i["short_description"]);
        $products = $products->flatMap(fn($i) => $i["variants"])
            ->map(fn($i) => [
                "code" => $i["sku"],
                "name" => $products_names->first(),
                "image_url" => $i["digital_assets"][0]["url"],
                "variant_name" => $i["color_description"],
            ])
            ->keyBy("code");
        ;

        return $stock->map(fn($i, $code) => [...$i, ...$products[$code]]);
    }

    private function getStockInfo(string $query = null): Collection
    {
        $res = Http::acceptJson()
            ->withHeader("x-Gateway-APIKey", env("MIDOCEAN_API_KEY"))
            ->get(self::URL . "stock/2.0", []);

        return $res->collect("stock")
            ->filter(fn($i) => preg_match("/$query/", $i["sku"]));
    }

    private function getProductInfo(string $sku): Collection
    {
        $res = Http::acceptJson()
            ->withHeader("x-Gateway-APIKey", env("MIDOCEAN_API_KEY"))
            ->get(self::URL . "products/2.0", [
                "language" => "pl",
            ]);

        return $res->collect()
            ->filter(fn($i) => preg_match("/$sku/", $i["variants"][0]["sku"]));
    }

    private function processFutureDelivery(array $data): string
    {
        if (!isset($data["first_arrival_date"]))
            return "brak";

        return $data["first_arrival_qty"] . " szt., ok. " . $data["first_arrival_date"];
    }
}
