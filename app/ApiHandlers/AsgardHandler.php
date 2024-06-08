<?php

namespace App\ApiHandlers;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class AsgardHandler extends ApiHandler
{
    private const URL = "https://developers.bluecollection.eu/";

    public function getData(string $params = null): Collection
    {
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
                "code" => $i["index"],
                "name" => collect($i["names"])->first(fn ($el) => $el["language"] == "pl")["title"],
                "image_url" => collect($i["image"])->sortBy("url")->first()["url"],
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
