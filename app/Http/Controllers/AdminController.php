<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\MainAttribute;
use App\Models\Product;
use App\Models\Variant;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
        "main-attributes",
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
        $mainAttributes = MainAttribute::all()->pluck("id", "name");

        return view("admin.product", compact(
            "product",
            "mainAttributes",
        ));
    }
    public function productImport()
    {
        return view("admin.product-import.form");
    }
    public function productImportFetch(Request $rq)
    {
        $data = collect();
        foreach (explode(";", $rq->product_code) as $product_code) {
            $data = $data->merge(StockController::stockDetails($product_code)["data"]);
        }
        $product_code = $rq->product_code;

        return view("admin.product-import.choose", array_merge(compact(
            "product_code",
            "data",
        )));
    }
    public function productImportChoose(Request $rq)
    {
        ["product_code" => $product_code, "data" => $data] = StockController::stockDetails($rq->product_code);

        foreach ($data as $i) {
            $product = Product::updateOrCreate(["id" => $i["code"]], [
                "name" => $i["name"],
                "description" => $i["description"],
            ]);

            if (count($product->images) == 0) {
                foreach ($i["image_url"] as $url) {
                    $contents = file_get_contents($url);
                    $filename = basename($url);
                    Storage::put("public/products/$product->id/$filename", $contents);
                }
            }
        }

        return redirect()->route("products")->with("success", "Zaimportowano produkty");
    }

    public function attributes()
    {
        $mainAttributes = MainAttribute::all();
        $attributes = Attribute::all();

        return view("admin.attributes", compact(
            "mainAttributes",
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
    public function mainAttributeEdit(int $id = null)
    {
        $attribute = ($id != null) ? MainAttribute::findOrFail($id) : null;

        return view("admin.main-attribute", compact("attribute"));
    }

    /////////////// updaters ////////////////

    public function updateProducts(Request $rq)
    {
        $form_data = $rq->except(["_token", "mode"]);
        $images = array_filter(explode(",", $form_data["images"]));
        $attributes = array_filter(explode(",", $form_data["attributes"]));

        if ($rq->mode == "save") {
            $product = Product::updateOrCreate(["id" => $rq->id], $form_data);

            foreach (Storage::allFiles("public/products/$product->id") as $image) {
                if (!in_array(env("APP_URL") . Storage::url($image), $images)) {
                    Storage::delete($image);
                }
            }
            foreach ($rq->file("newImages") ?? [] as $image) {
                $image->storeAs("public/products/$product->id", $image->getClientOriginalName());
            }

            $product->attributes()->sync($attributes);

            return redirect(route("products-edit", ["id" => $product->id]))->with("success", "Produkt został zapisany");
        } else if ($rq->mode == "delete") {
            Product::find($rq->id)->delete();
            Storage::deleteDirectory("public/products/$rq->id");
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
    public function updateMainAttributes(Request $rq)
    {
        $form_data = $rq->except(["_token", "mode", "id"]);
        if ($rq->mode == "save") {
            $attribute = MainAttribute::updateOrCreate(["id" => $rq->id], $form_data);
            return redirect(route("main-attributes-edit", ["id" => $attribute->id]))->with("success", "Atrybut został zapisany");
        } else if ($rq->mode == "delete") {
            MainAttribute::find($rq->id)->delete();
            return redirect(route("main-attributes"))->with("success", "Atrybut został usunięty");
        } else {
            abort(400, "Updater mode is missing or incorrect");
        }
    }
}
