<?php

namespace App\Http\Controllers;

use App\ApiHandlers\AsgardHandler;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function stockDetails(string $product_code)
    {
        $res = (new AsgardHandler())->call("getStockInfo", $product_code);

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
}
