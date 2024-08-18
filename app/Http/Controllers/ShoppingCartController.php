<?php

namespace App\Http\Controllers;

use App\Mail\Query;
use App\Mail\SendQueryConfirmed;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ShoppingCartController extends Controller
{
    private function getCart()
    {
        $attributes = Http::get(env("MAGAZYN_API_URL") . "attributes")->collect();

        return collect(session("cart"))
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
                "amount" => $item["amount"],
                "attachments" => Storage::allFiles("public/attachments/temp/" . session()->get("_token") . "/" . $key),
            ]);
    }

    public function index()
    {
        $cart = $this->getCart();

        return view("shopping-cart.index", compact(
            "cart",
        ));
    }

    public function add(Request $rq)
    {
        $next_no = $this->getCart()->count() == 0
            ? 0
            : $this->getCart()->max("no") + 1;

        $form_data = $rq->except(["_token", "files"]);
        $flattener = fn($val, $key) => !in_array($key, ["amount", "comment"]);
        if (collect($rq->session()->get("cart"))
            ->map(fn($item) => collect($item)->filter($flattener))
            ->contains(collect($form_data)->filter($flattener))
        ) {
            return back()->with("error", "Produkt jest już w koszyku");
        }
        $rq->session()->push("cart", $form_data);

        // temporary attachment storage
        foreach ($rq->file("files") as $file) {
            $file->storePubliclyAs(
                "public/attachments/temp/".session()->get("_token")."/".$next_no,
                $file->getClientOriginalName()
            );
        }

        return back()->with("success", "Produkt został dodany do koszyka");
    }

    public function mod(Request $rq)
    {
        $cart = collect($rq->session()->pull("cart"));

        if ($rq->has("delete")) {
            $rq->session()->put("cart", $cart
                ->filter(fn($item, $key) => $key != $rq->input("delete"))
                ->toArray()
            );

            Storage::deleteDirectory("public/attachments/temp/" . session()->get("_token") . "/" . $rq->input("delete"));

            return back()->with("success", "Produkt został usunięty z koszyka");
        }

        $rq->session()->put("cart", $cart
            ->map(fn($item, $i) => [
                ...$item,
                "amount" => $rq->amounts[$i],
                "comment" => $rq->comments[$i],
            ])
            ->toArray()
        );

        // attachments
        foreach ($cart as $no => $item) {
            foreach (Storage::allFiles("public/attachments/temp/" . session()->get("_token") . "/$no") as $file) {
                if (!in_array($file, explode(",", $rq->current_files[$no]))) {
                    Storage::delete($file);
                }
            }

            if (!isset($rq->file("files")[$no])) continue;
            foreach ($rq->file("files")[$no] as $file) {
                $file->storePubliclyAs(
                    "public/attachments/temp/".session()->get("_token")."/".$no,
                    $file->getClientOriginalName()
                );
            }
        }

        if ($rq->has("save")) return back()->with("success", "Koszyk został zapisany");

        return redirect()->route('prepare-query');
    }

    public function prepareQuery()
    {
        $cart = $this->getCart();

        return view("shopping-cart.prepare-query", compact(
            "cart",
        ));
    }

    public function sendQuery(Request $rq)
    {
        $cart = $this->getCart();
        $time = date("Y-m-d_H-i-s");

        // move attachments
        foreach (Storage::allFiles("public/attachments/temp/" . session()->get("_token")) as $file) {
            $file_path = Str::after($file, session()->get("_token") . "/");
            Storage::move($file, "public/attachments/$rq->email_address--$time/$file_path");
        }
        Storage::deleteDirectory("public/attachments/temp/".session()->get("_token"));

        $files = collect(Storage::allFiles("public/attachments/$rq->email_address--$time"))
            ->groupBy(fn ($file) => Str::beforeLast(Str::after($file, "public/attachments/$rq->email_address--$time/"), "/"));

        Mail::to(getSetting("query_email"))
            ->send(new Query(
                $rq->except(["_token", "attachments"]),
                $cart,
                $files
            ));
        Mail::to($rq->email_address)
            ->send(new SendQueryConfirmed(
                $rq->except(["_token", "attachments"]),
                $cart,
                $files
            ));

        $rq->session()->pull("cart");

        return redirect()->route("home")->with("success", "Zapytanie zostało wysłane");
    }
}
