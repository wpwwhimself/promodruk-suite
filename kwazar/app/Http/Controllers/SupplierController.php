<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SupplierController extends Controller
{
    public function list()
    {
        $suppliers = Supplier::orderBy("name")->get();

        return view("pages.suppliers.list", compact(
            "suppliers",
        ));
    }

    public function edit(int $id = null)
    {
        $supplier = $id
            ? Supplier::find($id)
            : null;

        $allowed_discounts = Supplier::ALLOWED_DISCOUNTS;
        $available_suppliers = Http::get(env("MAGAZYN_API_URL") . "suppliers")
            ->collect()
            ->filter(fn ($s) => $id
                ? Supplier::all()->contains("name", $s["name"])
                : Supplier::all()->doesntContain("name", $s["name"])
            )
            ->pluck("name", "name");

        return view("pages.suppliers.edit", compact(
            "supplier",
            "allowed_discounts",
            "available_suppliers",
        ));
    }

    public function process(Request $rq)
    {
        $form_data = $rq->except(["_token"]);
        $allowed_discounts = $rq->allowed_discounts;
        $custom_discounts = array_map(
            fn ($d) => json_decode($d, true),
            $rq->custom_discounts ?? []
        );

        if ($rq->mode == "save") {
            $supplier = Supplier::updateOrCreate(
                ["id" => $rq->id],
                array_merge(
                    $form_data,
                    compact("allowed_discounts", "custom_discounts"),
                )
            );
        } else if ($rq->mode == "delete") {
            Supplier::find($rq->id)->delete();
        }

        return redirect()->route("suppliers.list")->with("success", "Ustawienia dostawcy zmienione");
    }
}
