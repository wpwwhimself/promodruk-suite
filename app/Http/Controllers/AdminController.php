<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\Product;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public static $pages = [
        ["Ogólne", "dashboard"],
        ["Produkty", "products"],
        ["Cechy", "attributes"],
    ];

    public static $updaters = [
        "products",
        "attributes",
    ];

    /////////////// pages ////////////////

    public function dashboard()
    {
        $x = "";

        return view("admin.dashboard", compact(
            "x"
        ));
    }

    public function products()
    {
        $products = Product::all();

        return view("admin.products", compact(
            "products",
        ));
    }
    public function productEdit(string $id = null)
    {
        $product = ($id != null) ? Product::findOrFail($id) : null;

        return view("admin.product", compact(
            "product",
        ));
    }

    public function attributes()
    {
        $attributes = Attribute::all();

        return view("admin.attributes", compact(
            "attributes",
        ));
    }
    public function attributeEdit(int $id = null)
    {
        $attribute = ($id != null) ? Attribute::findOrFail($id) : null;
        $types = Attribute::$types;

        return view("admin.attribute", compact(
            "attribute",
            "types",
        ));
    }

    /////////////// updaters ////////////////

    public function updateProducts(Request $rq)
    {
        $form_data = $rq->except(["_token", "mode"]);
        $attributes = array_filter(explode(",", $form_data["attributes"]));

        if ($rq->mode == "save") {
            $product = Product::updateOrCreate(["id" => $rq->id], $form_data);

            $product->attributes()->sync($attributes);

            return redirect(route("products-edit", ["id" => $product->id]))->with("success", "Produkt został zapisany");
        } else if ($rq->mode == "delete") {
            Product::find($rq->id)->delete();
            return redirect(route("products"))->with("success", "Produkt został usunięty");
        } else {
            abort(400, "Updater mode is missing or incorrect");
        }
    }

    public function updateAttributes(Request $rq)
    {
        $form_data = $rq->except(["_token", "mode", "id", "variants"]);
        $variants_data = [];
        foreach($rq->variants["names"] as $i => $name) {
            if (empty($name)) continue;
            $variants_data[] = [
                "name" => $name,
                "value" => $rq->variants["values"][$i],
            ];
        }

        if ($rq->mode == "save") {
            if (!$rq->id) {
                $attribute = Attribute::create($form_data);
            } else {
                $attribute = Attribute::find($rq->id);
                $attribute->update($form_data);
            }

            $attribute->variants()->delete();
            $attribute->variants()->createMany($variants_data);

            return redirect(route("attributes-edit", ["id" => $attribute->id]))->with("success", "Atrybut został zapisany");
        } else if ($rq->mode == "delete") {
            Attribute::find($rq->id)->delete();
            return redirect(route("attributes"))->with("success", "Atrybut został usunięty");
        } else {
            abort(400, "Updater mode is missing or incorrect");
        }
    }
}
