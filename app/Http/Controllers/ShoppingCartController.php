<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ShoppingCartController extends Controller
{
    public function index()
    {
        $attributes = Http::get(env("MAGAZYN_API_URL") . "attributes")->collect();

        $cart = collect(session("cart"))
            ->map(fn($item, $key) => [
                "no" => $key,
                "product" => Product::find($item["product_id"]),
                "attributes" => collect($item)
                    ->filter(fn($val, $key) => Str::startsWith($key, "attr-"))
                    ->map(function ($val, $key) use ($attributes, $item) {
                        $attr = $attributes->firstWhere("id", str_replace("attr-", "", $key));
                        $var = collect($attr["variants"])->firstWhere("id", $item[$key]);
                        return compact("attr", "var");
                    }),
                "comment" => $item["comment"],
                "amount" => $item["amount"] ?? 0,
            ]);

        return view("shopping-cart.index", compact(
            "cart",
        ));
    }

    public function add(Request $rq)
    {
        $form_data = $rq->except("_token");
        $flattener = fn($val, $key) => !in_array($key, ["amount", "comment"]);
        if (collect($rq->session()->get("cart"))
            ->map(fn($item) => collect($item)->filter($flattener))
            ->contains(collect($form_data)->filter($flattener))
        ) {
            return redirect()->back()->with("error", "Produkt jest już w koszyku");
        }
        $rq->session()->push("cart", $form_data);
        return redirect()->back()->with("success", "Produkt został dodany do koszyka");
    }

    public function mod(Request $rq)
    {
        $cart = collect($rq->session()->pull("cart"));

        if ($rq->has("delete")) {
            $rq->session()->put("cart", $cart
                ->filter(fn($item, $key) => $key != $rq->input("delete"))
                ->toArray()
            );
            return redirect()->back()->with("success", "Produkt został usunięty z koszyka");
        }

        $rq->session()->put("cart", $cart
            ->map(fn($item, $i) => [
                ...$item,
                "amount" => $rq->amounts[$i],
                "comment" => $rq->comments[$i],
            ])
            ->toArray()
        );

        if ($rq->has("save")) return back()->with("success", "Koszyk został zapisany");

        return redirect()->route('prepare-quote');
    }

    public function prepareQuote()
    {
        return view("shopping-cart.prepare-quote");
    }
}
