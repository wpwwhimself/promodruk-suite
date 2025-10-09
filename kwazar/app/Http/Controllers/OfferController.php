<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OfferController extends Controller
{
    public function list()
    {
        $offers = (userIs("offer-master"))
            ? Offer::orderByDesc("created_at")
                ->paginate(25)
            : Offer::where("created_by", Auth::user()->id)
                ->orderByDesc("created_at")
                ->paginate(25);
        $document_formats = ["docx"]; //array_keys(DocumentOutputController::FORMATS);

        return view("pages.offers.list", compact(
            "offers",
            "document_formats",
        ));
    }

    public function offer($id = null)
    {
        $suppliers = Supplier::orderBy("name")->get();
        $offer = $id
            ? Offer::find($id)
            : null;

        if ($offer) {
            // check for missing products
            $magazyn_products = Http::post(env("MAGAZYN_API_URL") . "products/by/ids", [
                "ids" => collect($offer?->positions)->pluck("id")->toArray(),
            ])
                ->collect()
                ->pluck("id");
            $missing_positions = collect($offer->positions)
                ->filter(fn ($pos) => !$magazyn_products->contains($pos["id"]))
                ->map(fn ($pos) => [ ...$pos, "missing" => true ])
                ->keyBy("id");
            $updated_positions = collect($offer->positions)
                ->keyBy("id")
                ->merge($missing_positions)
                ->values();

            // backwards compatibility with grouped markings definition
            if (gettype(current(array_keys($updated_positions->first()["markings"]))) == "string") {
                $updated_positions = $updated_positions->map(fn ($p) => [
                    ...$p,
                    "markings" => collect($p["markings"])->flatten(1),
                ]);
            }

            $updated_positions = $updated_positions->toArray();
            $offer->update(["positions" => $updated_positions]);
        }

        return view("pages.offers.offer", compact(
            "offer",
            "suppliers",
        ));
    }

    public function prepare(Request $rq)
    {
        $products = $this->prepareProducts($rq);
        $user = Auth::user() ?? User::find($rq->user_id);
        $edited = $rq->get("edited");
        $showPricesPerUnit = $rq->has("show_prices_per_unit");
        $showGrossPrices = $rq->has("show_gross_prices");
        $showStocks = true;

        return view("components.offer.position-list", compact("products", "user", "edited", "showPricesPerUnit", "showGrossPrices", "showStocks"));
    }

    public function save(Request $rq)
    {
        $products = $this->prepareProducts($rq);
        $offer = Offer::updateOrCreate(
            ["id" => $rq->offer_id],
            [
                "name" => $rq->offer_name ?? now()->format("Y-m-d H:i"),
                "notes" => $rq->offer_notes,
                "unit_cost_visible" => $rq->has("show_prices_per_unit"),
                "gross_prices_visible" => $rq->has("show_gross_prices"),
                "stocks_visible" => $rq->has("show_stocks"),
                "positions" => $products,
            ]
        );
        $offer->files?->each(function ($file) {
            if ($file->file_path)
                Storage::disk("public")->delete($file->file_path);
            $file->update(["file_path" => null]);
        });

        return redirect()->route("offers.list")->with("success", "Oferta utworzona");
    }

    //////////////////////////////////////

    private function prepareProducts(Request $rq): Collection
    {
        $user = Auth::user() ?? User::find($rq->user_id);
        $suppliers = Supplier::all();

        $discounts = $rq->discounts;

        $products = Http::post(env("MAGAZYN_API_URL") . "products/by/ids", [
            "ids" => array_merge([$rq->product], $rq->product_ids ?? []),
            "include" => ["markings", "stock"],
        ])
            ->collect();

        $products = $products
            // get only what's necessary
            ->map(fn ($p) => array_filter($p, fn($k) => !in_array($k, [
                "original_sku",
                "tabs",
                "images",
                "thumbnails",
                "color",
            ]), ARRAY_FILTER_USE_KEY))
            ->map(fn ($p) => [
                ...$p,
                "marking_filters" => [
                    "positions" => collect($p["markings"])->mapWithKeys(fn ($i) => [$i["position"] => $i["position"]])->toArray(),
                    "techniques" => collect($p["markings"])
                        ->map(fn ($i) => preg_replace("/ \(\d+ kolor.*\)$/", "", $i["technique"]))
                        ->mapWithKeys(fn ($i) => [$i => $i])
                        ->toArray(),
                ],
            ])
            // filtering marking prices to given quantities
            ->map(fn ($p) => [
                ...$p,
                "markings" => collect($p["markings"])
                    ->map(fn ($m) => collect([
                        ...$m,
                        "surcharge" => $rq->global_surcharge ?? $rq->surcharge[$p["id"]][$m["position"]][$m["technique"]] ?? $user->global_surcharge,
                    ]))
                    ->map(fn ($m) => [
                        ...$m,
                        "quantity_prices" => collect($rq->quantities[$p["id"]] ?? [])
                            ->sort()
                            ->mapWithKeys(fn ($q) => [
                                $q => collect($m["quantity_prices"])
                                    ->last(fn ($data, $pricelist_quantity) => $pricelist_quantity <= $q)
                            ])
                            ->map(fn ($data, $quantity) => [
                                ...$data,
                                "price" => round(
                                    $data["price"]
                                    * (in_array("markings_discount", $suppliers->firstWhere("name", $p["product_family"]["source"])->allowed_discounts ?? [])
                                        && ($m["enable_discount"] ?? true)
                                        ? (1 - $discounts[$p["product_family"]["source"]]["markings_discount"] / 100)
                                        : 1
                                    )
                                    * (1 + $m["surcharge"] / 100)
                                , 2),
                            ])
                            ->toArray(),
                        "setup_price" => round(
                            $m["setup_price"]
                            * (in_array("markings_discount", $suppliers->firstWhere("name", $p["product_family"]["source"])->allowed_discounts ?? [])
                                && collect($m["quantity_prices"])->every("allow_discount_on_setup", true)
                                ? (1 - $discounts[$p["product_family"]["source"]]["markings_discount"] / 100)
                                : 1
                            )
                        , 2),
                    ]),
                "quantities" => collect($rq->quantities[$p["id"]] ?? [])
                    ->sort()
                    ->values()
                    ->toArray(),
                "surcharge" => $rq->global_surcharge ?? $rq->surcharge[$p["id"]]["product"] ?? $user->global_surcharge,
                "show_ofertownik_link" => $rq->has("show_ofertownik_link.$p[id]"),
                "additional_services" => collect($p["additional_services"] ?? [])
                    ->map(fn ($as, $i) => [
                        ...$as,
                        "surcharge" => $rq->global_surcharge ?? $rq->surcharge[$p["id"]]["additional_services"][$as["id"]] ?? $user->global_surcharge,
                    ])
                    ->map(fn ($as) => [
                        ...$as,
                        "price_per_unit" => round(
                            $as["price_per_unit"]
                            * (in_array("additional_services_discount", $suppliers->firstWhere("name", $p["product_family"]["source"])->allowed_discounts ?? [])
                                && ($p["enable_discount"] ?? true)
                                ? (1 - $discounts[$p["product_family"]["source"]]["additional_services_discount"] / 100)
                                : 1
                            )
                            * (1 + $as["surcharge"] / 100)
                        , 2),
                    ])
                    ->toArray(),
            ])
            ->map(fn ($p) => [
                ...$p,
                "price" => round(
                    $p["price"]
                    * (in_array("products_discount", $suppliers->firstWhere("name", $p["product_family"]["source"])->allowed_discounts ?? [])
                        && ($p["enable_discount"] ?? true)
                        ? (1 - ($suppliers->firstWhere("name", $p["product_family"]["source"])->custom_discounts->firstWhere("family_id", $p["product_family"]["id"])
                            ? $suppliers->firstWhere("name", $p["product_family"]["source"])->custom_discounts->firstWhere("family_id", $p["product_family"]["id"])["discount"]
                            : $discounts[$p["product_family"]["source"]]["products_discount"]
                        ) / 100)
                        : 1
                    )
                    * (1 + $p["surcharge"] / 100)
                , 2),
                "custom_discount" => $suppliers->firstWhere("name", $p["product_family"]["source"])->custom_discounts->firstWhere("family_id", $p["product_family"]["id"])["discount"] ?? null,

            ])
            ->map(fn ($p) => [
                ...$p,
                "calculations" => collect($rq->calculations[$p["id"]] ?? [])
                    ->map(fn ($calc) => [
                        "items" => collect($calc["items"] ?? [])
                            ->map(fn ($calc_item) => [
                                ...$calc_item,
                                "marking" => collect($p["markings"])
                                    ->firstWhere("id", Str::beforeLast($calc_item["code"], "_")),
                            ])
                            ->values(),
                        "additional_services" => collect($calc["additional_services"] ?? [])
                            ->map(fn ($item) => collect($p["additional_services"])->firstWhere("id", $item["code"]))
                            ->values(),
                        "pin_product" => $calc["pin_product"] ?? false,
                    ])
                    ->map(fn ($calc) => [
                        ...$calc,
                        "summary" => collect($p["quantities"])
                            ->mapWithKeys(function ($qty) use ($p, $calc) {
                                $product_price = $p["price"] + $p["manipulation_cost"];
                                $markings_price = 0;
                                $additionals_price = 0;

                                foreach ($calc["items"] as ["code" => $code, "marking" => $marking]) {
                                    if (empty($marking)) continue;

                                    $price_data = $marking["quantity_prices"][$qty];
                                    $mod_data = $marking["main_price_modifiers"][Str::afterLast($code, "_")] ?? [];

                                    $price_per_unit = $price_data["price"];
                                    $modifier = $mod_data["mod"] ?? "*1";
                                    $mod_price_per_unit = eval("return $price_per_unit $modifier;");
                                    $mod_setup = ($mod_data["include_setup"] ?? false)
                                        ? eval("return $marking[setup_price] $modifier;")
                                        : $marking["setup_price"];

                                    $added_marking_price = $mod_price_per_unit ?? 0;

                                    if (!($price_data["flat"] ?? false))
                                        $added_marking_price *= $qty;

                                    $markings_price += $mod_setup + $added_marking_price;
                                }

                                foreach ($calc["additional_services"] as ["label" => $label, "price_per_unit" => $price_per_unit]) {
                                    $additionals_price += $price_per_unit * $qty;
                                }

                                return [$qty => $markings_price + $additionals_price + $product_price * $qty];
                            })
                            ->toArray(),
                    ])
                    ->values() // reset keys
                    ->toArray(),
            ]);

        if ($rq->has("missing_products")) {
            $offer = Offer::find($rq->offer_id);
            $products = $products->merge(collect($offer->positions)
                ->filter(fn ($p) => $p["missing"] ?? false)
                ->toArray()
            );
        }

        return $products;
    }
}
