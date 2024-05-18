<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class StockController extends Controller
{
    private const ASGARD_URL = "https://developers.bluecollection.eu/";

    public function stockDetails(string $product_code)
    {
        if (empty(session("asgard_token")))
            $this->prepareAsgardToken();

        $res = Http::acceptJson()
            ->withToken(session("asgard_token"))
            ->get(self::ASGARD_URL . "/api/products/" . $product_code);

        $data = $res->json();

        return Inertia::render("StockDetails", compact(
            "product_code",
            "data",
        ));
    }

    private function prepareAsgardToken()
    {
        $res = Http::acceptJson()
            ->post(self::ASGARD_URL . "/api/token/", [
                "username" => env("ASGARD_API_LOGIN"),
                "password" => env("ASGARD_API_HASH_PASSWORD"),
            ]);
        session([
            "asgard_token" => $res->json("access"),
            "asgard_refresh_token" => $res->json("refresh"),
        ]);
    }
}
