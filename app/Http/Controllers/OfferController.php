<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

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
        $products = $this->prepareProducts($rq);
        $user = Auth::user() ?? User::find($rq->user_id);
        $showPricesPerUnit = $rq->has("show_prices_per_unit");

        return view("components.offer.position-list", compact("products", "user", "showPricesPerUnit"));
    }

    public function save(Request $rq)
    {
        $products = $this->prepareProducts($rq);
        Offer::create([
            "name" => $rq->offer_name ?? now()->format("Y-m-d H:i"),
            "positions" => $products,
        ]);

        return redirect()->route("offers.list")->with("success", "Oferta utworzona");
    }

    //////////////////////////////////////

    private function prepareProducts(Request $rq): Collection
    {
        $user = Auth::user() ?? User::find($rq->user_id);

        foreach ([
            "global_products_discount",
            "global_markings_discount",
            "global_surcharge",
        ] as $discount)
            $discounts[$discount] = $rq->{$discount};

        $products = Http::post(env("MAGAZYN_API_URL") . "products/by/ids", [
            "ids" => array_merge($rq->product_ids ?? [], [$rq->product]),
        ])
            ->collect();

        // filtering marking prices to given quantities
        $products = $products->map(fn ($p) => [
            ...$p,
            "markings" => collect($p["markings"])
                ->map(fn ($m) => collect([
                    ...$m,
                    "surcharge" => $discounts["global_surcharge"] ?? $rq->surcharge[$p["id"]][$m["position"]][$m["technique"]] ?? $user->global_surcharge,
                ]))
                ->map(fn ($m) => [
                    ...$m,
                    "quantity_prices" => collect($rq->quantities[$p["id"]] ?? [])
                        ->sort()
                        ->mapWithKeys(fn ($q) => [
                            $q => collect($m["quantity_prices"])
                                ->last(fn ($price_per_unit, $pricelist_quantity) => $pricelist_quantity <= $q)
                        ])
                        ->map(fn ($price_per_unit, $quantity) => $price_per_unit * (1 - $discounts["global_markings_discount"] / 100) * (1 + $m["surcharge"] / 100))
                        ->toArray(),
                ])
                ->groupBy("position"),
            "quantities" => collect($rq->quantities[$p["id"]] ?? [])
                ->sort()
                ->toArray(),
            "surcharge" => $discounts["global_surcharge"] ?? $rq->surcharge[$p["id"]]["product"] ?? $user->global_surcharge,
        ])
            ->map(fn ($p) => [
                ...$p,
                "price" => $p["price"] * (1 - $discounts["global_products_discount"] / 100) * (1 + $p["surcharge"] / 100),
            ])
            ->map(fn ($p) => [
                ...$p,
                "calculations" => collect($rq->calculations[$p["id"]] ?? [])
                    ->map(fn ($calc) => [
                        "items" => collect($calc)
                            ->map(fn ($calc_item) => [
                                ...$calc_item,
                                "marking" => collect($p["markings"])
                                    ->flatten(1)
                                    ->firstWhere("id", Str::beforeLast($calc_item["code"], "_")),
                            ])
                            ->toArray(),
                    ])
                    ->map(fn ($calc) => [
                        ...$calc,
                        "summary" => collect($p["quantities"])
                            ->mapWithKeys(function ($qty) use ($p, $calc) {
                                $sum_total = $p["price"];
                                foreach ($calc["items"] as ["code" => $code, "marking" => $marking]) {
                                    $sum_total += (
                                        (Str::contains($code, "_"))
                                        ? eval("return ".$marking["quantity_prices"][$qty]." ".$marking["main_price_modifiers"][Str::afterLast($code, "_")].";")
                                        : ($marking["quantity_prices"][$qty] ?? 0)
                                    );
                                }
                                return [$qty => $sum_total * $qty];
                            })
                            ->toArray(),
                    ])
                    ->toArray(),
            ]);

        return $products;
    }
}
