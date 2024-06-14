<?php

namespace App\Http\Controllers;

use App\DataIntegrators\AsgardHandler;
use App\DataIntegrators\AxpolHandler;
use App\DataIntegrators\MidoceanHandler;
use App\DataIntegrators\PARHandler;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function stockDetails(string $product_code)
    {
        $data = collect()
            ->merge((new AsgardHandler())->getDataWithPrefix($product_code))
            ->merge((new MidoceanHandler())->getDataWithPrefix($product_code))
            ->merge((new PARHandler())->getDataWithPrefix($product_code))
            ->merge((new AxpolHandler())->getDataWithPrefix($product_code))
        ;

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
