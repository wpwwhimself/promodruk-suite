<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class StockController extends Controller
{
    private const ASGARD_URL = "https://developers.bluecollection.eu/";

    public function stockDetails(string $product_code)
    {
        $res = $this->callAsgard("getStockInfo", $product_code);

        $data = $res->collect("results");

        return view("stock", array_merge(
            [
                "title" => implode(" | ", [$product_code, "Stan magazynowy"]),
                "now" => Carbon::now(),
            ],
            compact(
                "product_code",
                "data",
            )
        ));
    }

    private function callAsgard(string $func_name, string $params = null)
    {
        if (empty(session("asgard_token")))
            $this->prepareAsgardToken();

        $res = $this->{$func_name."Asgard"}($params);

        if ($res->unauthorized()) {
            $this->refreshAsgardToken();
            $res = $this->{$func_name."Asgard"}($params);
        }
        if ($res->unauthorized()) {
            $this->prepareAsgardToken();
            $res = $this->{$func_name."Asgard"}($params);
        }

        return $res;
    }

    private function prepareAsgardToken()
    {
        $res = Http::acceptJson()
            ->post(self::ASGARD_URL . "api/token/", [
                "username" => env("ASGARD_API_LOGIN"),
                "password" => env("ASGARD_API_HASH_PASSWORD"),
            ]);
        session([
            "asgard_token" => $res->json("access"),
            "asgard_refresh_token" => $res->json("refresh"),
        ]);
    }

    private function refreshAsgardToken()
    {
        $res = Http::acceptJson()
            ->withToken(session("asgard_token"))
            ->post(self::ASGARD_URL . "api/token/refresh", [
                "refresh" => session("asgard_refresh_token"),
            ]);
        session("asgard_token", $res->json("access"));
    }

    private function getStockInfoAsgard(string $query = null)
    {
        return Http::acceptJson()
            ->withToken(session("asgard_token"))
            ->get(self::ASGARD_URL . "api/products-index", [
                "index" => $query,
            ]);
    }
}
