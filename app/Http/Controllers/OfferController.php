<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class OfferController extends Controller
{
    public function offer()
    {
        $products = [];

        return view("pages.offers.offer", compact(
            "products",
        ));
    }

    public function prepare(Request $rq)
    {
        $products = Http::post(env("MAGAZYN_API_URL") . "products/by/ids", [
            "ids" => $rq->product_ids,
        ])
            ->collect();

        // filtering marking prices to given quantities
        $products = $products->map(fn ($p) => [
            ...$p,
            "markings" => collect($p["markings"])
                ->map(fn ($m) => collect([
                    ...$m,
                    "quantity_prices" => collect($rq->quantities[$p["id"]] ?? [])
                        ->sort()
                        ->mapWithKeys(fn ($q) => [
                            $q => collect($m["quantity_prices"])
                                ->last(fn ($price_per_unit, $pricelist_quantity) => $pricelist_quantity <= $q)
                        ])
                        ->toArray(),
                ]))
                ->groupBy("position"),
            "quantities" => collect($rq->quantities[$p["id"]] ?? [])
                ->sort()
                ->toArray(),
        ]);

        return view("pages.offers.offer", compact(
            "products",
        ));
    }

    public function update(Request $rq)
    {
        dd($rq->all());
    }
}
