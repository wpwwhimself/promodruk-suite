<?php

namespace App\DataIntegrators;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class EasygiftsHandler extends ApiHandler
{
    private const URL = "https://www.easygifts.com.pl/data/webapi/pl/json/";
    public function getPrefix(): string { return "EA"; }

    public function getData(string $params = null): Collection
    {
        $prefix = substr($params, 0, strlen($this->getPrefix()));
        if ($prefix == $this->getPrefix()) $params = substr($params, strlen($this->getPrefix()));

        $stock = $this->getStockInfo($params)
            ->map(fn($i) => [
                "code" => $this->getPrefix() . $i["CodeFull"],
                "quantity" => $i["Quantity24h"] /* + $i["Quantity37days"]*/,
                "future_delivery" => $this->processFutureDelivery($i),
            ])
            ->keyBy("code");

        $products = $this->getProductInfo($params)
            ->map(fn($i) => [
                "code" => $this->getPrefix() . $i["CodeFull"],
                "name" => $i["Name"],
                "image_url" => collect($i["Images"])->sort()->first(),
                "variant_name" => $i["ColorName"],
            ])
            ->keyBy("code");

        return $stock->map(fn($i, $code) => [...$i, ...$products[$code]]);
    }

    private function getStockInfo(string $query = null): Collection
    {
        $res = Http::acceptJson()
            ->get(self::URL . "stocks.json", [])
            ->collect();

        $header = $res[0];
        $res = $res->skip(1)
            ->map(fn($row) => array_combine($header, $row));

        return $res->filter(fn($i) => preg_match("/$query/", $i["CodeFull"]));
    }

    private function getProductInfo(string $query = null)
    {
        $res = Http::acceptJson()
            ->get(self::URL . "offer.json", [])
            ->collect();

        $header = $res[0];
        $res = $res->skip(1)
            ->map(fn($row) => array_combine($header, $row));

        return $res->filter(fn($i) => preg_match("/$query/", $i["CodeFull"]));
    }

    private function processFutureDelivery(array $future_delivery) {
        if ($future_delivery["QuantityDelivery"] == 0)
            return "brak";

        return $future_delivery["QuantityDelivery"] . " szt., ok. "; //TODO dodać datę wysyłki, jak tylko mi odpiszą
    }
}
