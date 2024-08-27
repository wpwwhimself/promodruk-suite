<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\MainAttribute;
use App\Models\Product;
use App\Models\ProductSynchronization;
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
        ["Synchronizacje", "synchronizations"],
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
        $search = request("search", "");
        $products = Product::where("name", "like", "%$search%")
            ->orWhere("id", "like", "%$search%")
            ->orWhere("description", "like", "%$search%")
            ->orderBy("name")
            ->paginate(30);

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

    public function synchronizations()
    {
        $synchronizations = ProductSynchronization::all();

        return view("admin.synchronizations", compact(
            "synchronizations",
        ));
    }

    /////////////// updaters ////////////////

    public function updateProducts(Request $rq)
    {
        $form_data = $rq->except(["_token", "mode"]);
        $images = array_filter(explode(",", $form_data["images"] ?? ""));
        $attributes = array_filter(explode(",", $form_data["attributes"] ?? ""));

        if ($rq->mode == "save") {
            $product = Product::updateOrCreate(["id" => $rq->id], $form_data);

            foreach (Storage::allFiles("public/products/$product->id/images") as $image) {
                if (!in_array(env("APP_URL") . Storage::url($image), $images)) {
                    Storage::delete($image);
                }
            }
            foreach ($rq->file("newImages") ?? [] as $image) {
                $image->storeAs("public/products/$product->id/images", $image->getClientOriginalName());
            }

            $product->attributes()->sync($attributes);

            return redirect(route("products-edit", ["id" => $product->id]))->with("success", "Produkt został zapisany");
        } else if ($rq->mode == "delete") {
            $product = Product::find($rq->id);
            $product->attributes()->detach();
            $product->delete();
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

    /////////////////////////////////////////

    public function synchEnable(string $supplier_name, string $mode, bool $enabled)
    {
        ProductSynchronization::where("supplier_name", $supplier_name)->update([$mode."_import_enabled" => $enabled]);
        return back()->with("success", "Status synchronizacji został zmieniony");
    }
    public function synchReset(string $supplier_name)
    {
        ProductSynchronization::where("supplier_name", $supplier_name)->update([
            "product_import_enabled" => true,
            "stock_import_enabled" => true,
            "progress" => 0,
            "current_external_id" => null,
        ]);
        return back()->with("success", "Synchronizacja została zresetowana");
    }
}
