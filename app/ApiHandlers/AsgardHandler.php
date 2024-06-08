<?php

namespace App\ApiHandlers;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class AsgardHandler extends ApiHandler
{
    private const URL = "https://developers.bluecollection.eu/";

    public function call(string $func_name, string $params = null): Response
    {
        if (empty(session("asgard_token")))
            $this->prepareToken();

        $res = $this->{$func_name}($params);

        if ($res->unauthorized()) {
            $this->refreshToken();
            $res = $this->{$func_name}($params);
        }
        if ($res->unauthorized()) {
            $this->prepareToken();
            $res = $this->{$func_name}($params);
        }

        return $res;
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
}
