<?php

namespace App\DataIntegrators;

use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class PARHandler extends ApiHandler
{
    private const URL = "https://www.par.com.pl/api/";
    public function getPrefix(): string { return "R"; }

    public function getData(string $params = null): Collection
    {
        $stock = $this->getStockInfo($params)
            ->map(function ($i) {
                $product = $this->getOneProductInfo($i["kod"]);
                return [
                    "code" => $i["kod"],
                    "name" => $product["nazwa"],
                    "description" => $product["opis"],
                    "image_url" => array_map(fn($i) => "https://www.par.com.pl" . $i["zdjecie"], $product["zdjecia"]),
                    "variant_name" => $product["kolor_podstawowy"],
                    "quantity" => $i["stan_magazynowy"],
                    "future_delivery" => $this->processFutureDelivery($i),
                ];
            });

        return $stock;
    }

    private function getStockInfo(string $query = null): Collection
    {
        $res = Http::acceptJson()
            ->withBasicAuth(env("PAR_API_LOGIN"), env("PAR_API_PASSWORD"))
            ->get(self::URL . "stocks.json", []);

        return $res->collect("products")
            ->map(fn($i) => $i["product"])
            ->filter(fn($i) => preg_match("/$query/", $i["kod"]));
    }

    private function getOneProductInfo(string $sku): Collection
    {
        $res = Http::acceptJson()
            ->withBasicAuth(env("PAR_API_LOGIN"), env("PAR_API_PASSWORD"))
            ->post(self::URL . "products/code", [
                "code" => $sku
            ]);

        return $res->collect();
    }

    private function processFutureDelivery(array $data): string
    {
        if ($data["ilosc_dostawy"] == 0)
            return "brak";

        return $data["ilosc_dostawy"] . " szt., ok. " . Carbon::parse($data["data_dostawy"])->format("Y-m-d");
    }
}
