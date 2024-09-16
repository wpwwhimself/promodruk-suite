<?php

namespace App\Http\Controllers;

use App\Console\Kernel;
use App\Models\Attribute;
use App\Models\MainAttribute;
use App\Models\Product;
use App\Models\ProductSynchronization;
use App\Models\Stock;
use App\Models\Variant;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public static $pages = [
        ["Ogólne", "dashboard", null],
        ["Produkty", "products", "Edytor"],
        ["Cechy", "attributes", "Edytor"],
        ["Synchronizacje", "synchronizations", "Administrator"],
    ];

    public static $updaters = [
        "products",
        "attributes",
        "main-attributes",
    ];

    public const CUSTOM_PRODUCT_PREFIX = "ZR";

    /////////////// pages ////////////////

    public function dashboard()
    {
        return view("admin.dashboard");
    }

    public function products()
    {
        if (!userIs("Edytor")) abort(403);

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
        if (!userIs("Edytor")) abort(403);

        $product = ($id != null) ? Product::findOrFail($id) : null;
        $mainAttributes = MainAttribute::all()->pluck("id", "name");

        $isCustom = !$id || Str::startsWith($id, self::CUSTOM_PRODUCT_PREFIX);

        return view("admin.product", compact(
            "product",
            "mainAttributes",
            "isCustom",
        ));
    }

    public function attributes()
    {
        if (!userIs("Edytor")) abort(403);

        $mainAttributes = MainAttribute::orderBy("name")->get();
        $attributes = Attribute::orderBy("name")->get();
        $productExamples = Product::all()
            ->groupBy("original_color_name");

        return view("admin.attributes", compact(
            "mainAttributes",
            "attributes",
            "productExamples",
        ));
    }
    public function attributeEdit(int $id = null)
    {
        if (!userIs("Edytor")) abort(403);

        $attribute = ($id != null) ? Attribute::findOrFail($id) : null;
        $types = Attribute::$types;

        return view("admin.attribute", compact(
            "attribute",
            "types",
        ));
    }
    public function mainAttributeEdit(int $id = null)
    {
        if (!userIs("Edytor")) abort(403);

        $attribute = ($id != null) ? MainAttribute::findOrFail($id) : null;

        return view("admin.main-attribute", compact("attribute"));
    }
    public function mainAttributePrune()
    {
        if (!userIs("Administrator")) abort(403);
        MainAttribute::whereNotIn("name", Product::pluck("original_color_name")->unique())->delete();
        return back()->with("success", "Nieużywane cechy podstawowe zostały usunięte");
    }

    public function synchronizations()
    {
        if (!userIs("Administrator")) abort(403);

        foreach (Kernel::INTEGRATORS as $integrator) {
            ProductSynchronization::firstOrCreate(["supplier_name" => $integrator]);
        }
        $synchronizations = ProductSynchronization::all();


        return view("admin.synchronizations", compact(
            "synchronizations",
        ));
    }

    /////////////// updaters ////////////////

    public function updateProducts(Request $rq)
    {
        if (!userIs("Edytor")) abort(403);

        $form_data = $rq->except(["_token", "mode"]);
        $images = array_filter(explode(",", $form_data["images"] ?? ""));
        $thumbnails = array_filter(explode(",", $form_data["thumbnails"] ?? ""));
        $attributes = array_filter(explode(",", $form_data["attributes"] ?? ""));

        // translate tab tables contents (labels, values)
        foreach ($rq->tabs ?? [] as $i => $tab) {
            foreach ($tab["cells"] as $j => $cell) {
                if (!in_array($cell["type"], ["table", "tiles"])) continue 2;

                $form_data["tabs"][$i]["cells"][$j]["content"] = array_combine(
                    $cell["content"]["labels"],
                    $cell["content"]["values"],
                );
            }
        }

        if ($rq->mode == "save") {
            $product = Product::updateOrCreate(["id" => $rq->id], $form_data);

            foreach (["images", "thumbnails"] as $type) {
                foreach (Storage::allFiles("public/products/$product->id/$type") as $image) {
                    if (!in_array(env("APP_URL") . Storage::url($image), $$type)) {
                        Storage::delete($image);
                    }
                }
                foreach ($rq->file("new".ucfirst($type)) ?? [] as $image) {
                    $image->storeAs("public/products/$product->id/$type", $image->getClientOriginalName());
                }
            }

            $product->attributes()->sync($attributes);

            $stock = Stock::firstOrCreate(["id" => $product->id], [
                "current_stock" => 0,
            ]);

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
        if (!userIs("Edytor")) abort(403);

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
        if (!userIs("Edytor")) abort(403);

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

    public function getSynchData(Request $rq)
    {
        $synchronizations = ProductSynchronization::all();
        return response()->json($synchronizations);
    }
    public function synchEnable(string $supplier_name, string $mode, bool $enabled)
    {
        if (!userIs("Administrator")) abort(403);

        ProductSynchronization::where("supplier_name", $supplier_name)->update([$mode."_import_enabled" => $enabled]);
        return back()->with("success", "Status synchronizacji został zmieniony");
    }
    public function synchReset(?string $supplier_name = null)
    {
        if (!userIs("Administrator")) abort(403);

        if (empty($supplier_name)) {
            foreach (Kernel::INTEGRATORS as $integrator) {
                ProductSynchronization::where("supplier_name", $integrator)->update([
                    "product_import_enabled" => true,
                    "stock_import_enabled" => true,
                    "progress" => 0,
                    "current_external_id" => null,
                    "synch_status" => null,
                ]);
                Cache::forget("synch_".strtolower($integrator)."_in_progress");
            }
        } else {
            ProductSynchronization::where("supplier_name", $supplier_name)->update([
                "product_import_enabled" => true,
                "stock_import_enabled" => true,
                "progress" => 0,
                "current_external_id" => null,
                "synch_status" => null,
            ]);
            Cache::forget("synch_".strtolower($supplier_name)."_in_progress");
        }

        return back()->with("success", "Synchronizacja została zresetowana");
    }
}
