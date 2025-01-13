<?php

namespace App\Http\Controllers;

use App\Console\Kernel;
use App\Models\Attribute;
use App\Models\MainAttribute;
use App\Models\Product;
use App\Models\ProductFamily;
use App\Models\ProductSynchronization;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdminController extends Controller
{
    #region constants
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
    #endregion

    #region pages
    public function dashboard()
    {
        return view("admin.dashboard");
    }

    public function products()
    {
        if (!userIs("Edytor")) abort(403);

        $suppliers = ProductFamily::distinct("source")
            ->orderBy("source")
            ->get()
            ->pluck("source", "source")
            ->filter()
            ->merge(["Produkty własne" => "custom"]);
        $families = ProductFamily::with("products")
            ->where(fn ($q) => $q
                ->where("name", "like", "%".request("search")."%")
                ->orWhere("id", "like", "%".request("search")."%")
                ->orWhere("description", "like", "%".request("search")."%")
            )
            ->where(fn ($q) => $q
                ->where("source", request("supplier"))
                ->orWhereRaw(request("supplier") == "custom" ? "source is null" : "false")
                ->orWhereRaw(var_export(empty(request("supplier")), true))
            )
            ->orderBy("name")
            ->paginate(102);

        return view("admin.products", compact(
            "families",
            "suppliers",
        ));
    }
    public function productFamilyEdit(string $id = null)
    {
        $family = ($id != null) ? ProductFamily::findOrFail($id) : null;
        $isCustom = !$id || Str::startsWith($id, self::CUSTOM_PRODUCT_PREFIX);

        return view("admin.product.family", compact(
            "family",
            "isCustom",
        ));
    }
    public function productEdit(string $id = null)
    {
        if (!userIs("Edytor")) abort(403);

        $product = ($id != null) ? Product::findOrFail($id) : null;
        $mainAttributes = MainAttribute::all()->pluck("id", "name");
        $attributes = Attribute::orderBy("name")->get()->pluck("id", "name");

        $isCustom = !$id || Str::startsWith($id, self::CUSTOM_PRODUCT_PREFIX);

        $copyFrom = (request("copy_from"))
            ? Product::find(request("copy_from"))
                ?? ProductFamily::find(request("copy_from"))
            : null;

        return view("admin.product.index", compact(
            "product",
            "mainAttributes",
            "attributes",
            "isCustom",
            "copyFrom",
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
    #endregion

    #region updaters
    public function updateProducts(Request $rq)
    {
        if (!userIs("Edytor")) abort(403);

        [
            "form_data" => $form_data,
            "images" => $images,
            "thumbnails" => $thumbnails,
            "attributes" => $attributes,
        ] = prepareFormData($rq, [
            "enable_discount" => "bool",
            "price" => "number",
            "images" => "array",
            "thumbnails" => "array",
            "attributes" => "array",
        ], ["images", "thumbnails", "attributes"]);

        $form_data["id"] ??= $form_data["product_family_id"] . $form_data["id_suffix"];
        // translate tab tables contents (labels, values)
        $form_data["tabs"] = json_decode($rq->tabs, true) ?? null;

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

    public function updateProductFamilies(Request $rq)
    {
        [
            "form_data" => $form_data,
            "images" => $images,
            "thumbnails" => $thumbnails,
        ] = prepareFormData($rq, [
            "images" => "array",
            "thumbnails" => "array",
        ], ["images", "thumbnails"]);

        $form_data["original_sku"] ??= $form_data["id"];
        // translate tab tables contents (labels, values)
        $form_data["tabs"] = json_decode($rq->tabs, true) ?? null;

        if ($rq->mode == "save") {
            $family = ProductFamily::updateOrCreate(["id" => $rq->id], $form_data);

            foreach (["images", "thumbnails"] as $type) {
                foreach (Storage::allFiles("public/products/$family->id/$type") as $image) {
                    if (!in_array(env("APP_URL") . Storage::url($image), $$type)) {
                        Storage::delete($image);
                    }
                }
                foreach ($rq->file("new".ucfirst($type)) ?? [] as $image) {
                    $image->storeAs("public/products/$family->id/$type", $image->getClientOriginalName());
                }
            }

            return redirect(route("products-edit-family", ["id" => $family->id]))->with("success", "Produkt został zapisany");
        } else if ($rq->mode == "delete") {
            $family = ProductFamily::find($rq->id);
            $family->delete();
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
    #endregion

    #region helpers
    /**
     * takes tab builder data from request and renders the editor
     */
    public function prepareProductTabs(Request $rq): View
    {
        $tabs = json_decode($rq->tabs, true);
        $editable = ($rq->source == null);
        return view("components.product.tabs-editor", compact("tabs", "editable"));
    }

    /**
     *
     */
    public function getOriginalCategories(string $input_id, ?string $query): View
    {
        $hints = ProductFamily::where("original_category", "like", "%$query%")
            ->orderBy("original_category")
            ->select("original_category")
            ->distinct()
            ->get()
            ->pluck("original_category")
            ->take(10);
        return view("components.product.original-categories-hints", compact("hints", "input_id"));
    }
    #endregion

    #region synchronization
    public function getSynchData(Request $rq)
    {
        $synchronizations = ProductSynchronization::all();
        $sync_statuses = ProductSynchronization::selectRaw("sum(product_import_enabled) as product, sum(stock_import_enabled) as stock, sum(marking_import_enabled) as marking")
            ->get()
            ->first();
        return view("components.synchronizations.table", compact("synchronizations", "sync_statuses"));
    }
    public function synchEnable(Request $rq)
    {
        if (!userIs("Administrator")) abort(403);

        ProductSynchronization::whereRaw(empty($rq->supplier_name) ? "true" : "supplier_name = '$rq->supplier_name'")
            ->update([$rq->mode."_import_enabled" => $rq->enabled]);
        return response()->json("Status synchronizacji został zmieniony");
    }
    public function synchReset(Request $rq)
    {
        if (!userIs("Administrator")) abort(403);

        if (empty($rq->supplier_name)) {
            foreach (Kernel::INTEGRATORS as $integrator) {
                ProductSynchronization::where("supplier_name", $integrator)->update([
                    "product_import_enabled" => true,
                    "stock_import_enabled" => true,
                    "marking_import_enabled" => true,
                    "progress" => 0,
                    "current_external_id" => null,
                    "synch_status" => null,
                ]);
                Cache::forget("synch_".strtolower($integrator)."_in_progress");
            }
        } else {
            ProductSynchronization::where("supplier_name", $rq->supplier_name)->update([
                "product_import_enabled" => true,
                "stock_import_enabled" => true,
                "marking_import_enabled" => true,
                "progress" => 0,
                "current_external_id" => null,
                "synch_status" => null,
            ]);
            Cache::forget("synch_".strtolower($rq->supplier_name)."_in_progress");
        }

        return response()->json("Synchronizacja została zresetowana");
    }
    #endregion
}
