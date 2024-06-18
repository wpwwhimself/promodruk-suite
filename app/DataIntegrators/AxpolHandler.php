<?php

namespace App\DataIntegrators;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class AxpolHandler extends ApiHandler
{
    private const URL = "https://axpol.com.pl/api/b2b-api/";
    public function getPrefix(): array { return ["V", "P", "T"]; }

    public function getData(string $params = null): Collection
    {
        $prefix_length = max(array_map(fn($i) => strlen($i), $this->getPrefix()));
        $prefix = substr($params, 0, $prefix_length);
        if (in_array($prefix, $this->getPrefix())) $params = substr($params, strlen($prefix_length));

        $this->prepareToken();
        dd(session("axpol_token"));

        $res = $this->getStockInfo($params);

        return $res->map(fn($i) => [
                "code" => $this->getPrefix() . $i["CodeERP"],
                "name" => $i["DescriptionPL"],
                "image_url" => self::URL . $i["Foto01"],
                "variant_name" => $i["ColorPL"],
                "quantity" => $i["InStock"],
                "future_delivery" => $this->processFutureDelivery($i),
            ]);
    }

    private function prepareToken()
    {
        $res = Http::acceptJson()
            ->post(self::URL . "", [
                "method" => "Customer.Login",
                "key" => env("AXPOL_API_SECRET"),
                "username" => env("AXPOL_API_LOGIN"),
                "password" => env("AXPOL_API_PASSWORD"),
            ]);
        // dd($res->body());
        session([
            "axpol_uid" => $res["uid"],
            "axpol_token" => $res["jwt"],
        ]);
    }

    private function getStockInfo(string $query = null)
    {
        return Http::acceptJson()
            ->withToken(session("axpol_token"))
            ->get(self::URL . "", [
                "key" => env("AXPOL_API_SECRET"),
                "uid" => session("axpol_uid"),
                "method" => "Product.List",
                "params[date]" => date("Y-m-d H:i:s"),
            ])
            ->collect("data")
            ->filter(fn($i) => preg_match("/$query/", $i["CodeERP"]));
    }

    private function processFutureDelivery(array $data) {
        if (empty($data["nextDelivery"]))
            return "brak";

        return $data["nextDelivery"] . " szt., ok. " . $data["Days"]; //TODO ustalić, czy Days to to
    }
}
