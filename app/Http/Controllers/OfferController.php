<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class OfferController extends Controller
{
    public function list()
    {
        $offers = (userIs("offer-master"))
            ? Offer::orderByDesc("created_at")
                ->get()
            : Offer::where("created_by", Auth::user()->id)
                ->orderByDesc("created_at")
                ->get();

        return view("pages.offers.list", compact(
            "offers",
        ));
    }

    public function offer()
    {
        $products = [];

        return view("pages.offers.offer", compact(
            "products",
        ));
    }

    public function prepare(Request $rq)
    {
        foreach ([
            "global_products_discount",
            "global_markings_discount",
            "global_surcharge",
        ] as $discount)
            $discounts[$discount] = $rq->{$discount};

        $products = Http::post(env("MAGAZYN_API_URL") . "products/by/ids", [
            "ids" => $rq->product_ids,
        ])
            ->collect()
            ->map(fn ($p) => [
                ...$p,
                "price" => $p["price"] * (1 - $discounts["global_products_discount"] / 100),
            ]);

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
                        ->map(fn ($price_per_unit, $quantity) => $price_per_unit * (1 - $discounts["global_markings_discount"] / 100))
                        ->toArray(),
                    "surcharge" => $discounts["global_surcharge"] ?? $rq->surcharge[$p["id"]][$m["technique"]] ?? 0,
                ]))
                ->groupBy("position"),
            "quantities" => collect($rq->quantities[$p["id"]] ?? [])
                ->sort()
                ->toArray(),
            "surcharge" => $discounts["global_surcharge"] ?? $rq->surcharge[$p["id"]]["product"] ?? 0,
        ]);

        // clear global surcharge if applied
        $discounts["global_surcharge"] = null;

        return view("pages.offers.offer", compact(
            "products",
            "discounts",
        ));
    }

    public function update(Request $rq)
    {
        dd($rq->all());
    }
}
