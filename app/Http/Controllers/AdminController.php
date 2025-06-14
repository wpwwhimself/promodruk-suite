<?php

namespace App\Http\Controllers;

use App\Models\AltAttribute;
use App\Models\CustomSupplier;
use App\Models\MainAttribute;
use App\Models\PrimaryColor;
use App\Models\Product;
use App\Models\ProductFamily;
use App\Models\ProductSynchronization;
use DOMDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use SimpleXMLElement;

class AdminController extends Controller
{
    #region constants
    public static $pages = [
        ["Ogólne", "dashboard", null],
        ["Produkty", "products", "Edytor"],
        ["Cechy", "attributes", "Edytor"],
        ["Dostawcy", "suppliers", "Edytor"],
        ["Pliki", "files", "Edytor"],
        ["Synchronizacje", "synchronizations", "Administrator"],
    ];

    public static $updaters = [
        "products",
        "main-attributes",
        "suppliers",
        "synchronizations",
    ];
    #endregion

    #region pages
    public function dashboard()
    {
        return view("admin.dashboard");
    }

    public function products()
    {
        if (!userIs("Edytor")) abort(403);

        $perPage = request("perPage", 102);

        $suppliers = ProductSynchronization::all()
            ->pluck("supplier_name", "supplier_name")
            ->merge(CustomSupplier::all()
                ->mapWithKeys(fn ($supplier) => [$supplier->name => ProductFamily::CUSTOM_PRODUCT_GIVEAWAY.$supplier->id])
            )
            ->sortKeys();
        $families = ProductFamily::with("products")->orderBy("name");
        if (request("search")) {
            $families = $families->where(fn ($q) => $q
                ->where("name", "like", "%".request("search")."%")
                // PrefixedId
                ->orWhereRaw("coalesce(
                    replace(id, '".ProductFamily::CUSTOM_PRODUCT_GIVEAWAY."', (select prefix from custom_suppliers where id = replace(source, '".ProductFamily::CUSTOM_PRODUCT_GIVEAWAY."', ''))),
                    id
                ) like '%".request("search")."%'")
                ->orWhere("description", "like", "%".request("search")."%")
            );
        }
        if (request("supplier")) {
            $families = $families->where("source", request("supplier"));
        }
        $families = $families->paginate($perPage);

        return view("admin.products", compact(
            "families",
            "suppliers",
        ));
    }
    public function productFamilyEdit(?string $id = null)
    {
        // check if product is custom and substitute $id
        if (Str::startsWith($id, CustomSupplier::prefixes())) {
            $id = ProductFamily::getByPrefixedId($id)->id;
        }

        $family = ($id != null) ? ProductFamily::findOrFail($id) : null;
        $isCustom = $family?->is_custom ?? true;
        $suppliers = $isCustom
            ? CustomSupplier::orderBy("name")->get()->pluck("id", "name")
            : [$family?->source => $family?->source];
        $categories = $isCustom
            ? ($family?->source
                ? CustomSupplier::where("id", Str::after($family?->source, ProductFamily::CUSTOM_PRODUCT_GIVEAWAY))->first()->categories
                : collect()
            )
            : collect([$family?->original_category]);
        $categories = collect($categories)->combine($categories);
        $altAttributes = AltAttribute::orderBy("name")->get()->pluck("id", "name");

        return view("admin.product.family", compact(
            "family",
            "suppliers",
            "categories",
            "isCustom",
            "altAttributes",
        ));
    }
    public function productEdit(?string $id = null)
    {
        if (!userIs("Edytor")) abort(403);

        // check if product is custom and substitute $id
        if (Str::startsWith($id, CustomSupplier::prefixes())) {
            $id = Product::getByFrontId($id)->id;
        }

        $product = ($id != null) ? Product::findOrFail($id) : null;
        $primaryColors = PrimaryColor::orderBy("name")->get()->pluck("name", "name");

        $isCustom = $product?->isCustom ?? true;

        $copyFrom = (request("copy_from"))
            ? Product::find(request("copy_from"))
                ?? ProductFamily::find(request("copy_from"))
            : null;

        return view("admin.product.index", compact(
            "product",
            "primaryColors",
            "isCustom",
            "copyFrom",
        ));
    }

    public function attributes()
    {
        if (!userIs("Edytor")) abort(403);

        $mainAttributes = MainAttribute::orderBy("main_attributes.name");
        // $productExamples = Product::with("productFamily")->get()
        //     ->groupBy(["variant_name", "productFamily.source"]);

        if (request("main_attr_q")) {
            $mainAttributes = $mainAttributes
                ->leftJoin("products", "products.variant_name", "=", "main_attributes.name")
                ->where("main_attributes.name", "regexp", request("main_attr_q"))
                ->orWhere("products.id", "regexp", request("main_attr_q"))
                ->select("main_attributes.*")
                ->distinct();
        }

        $mainAttributes = $mainAttributes->get();

        return view("admin.attributes", compact(
            "mainAttributes",
            // "productExamples",
        ));
    }
    public function mainAttributeEdit(int $id)
    {
        if (!userIs("Edytor")) abort(403);

        $attribute = MainAttribute::findOrFail($id);

        $primaryColors = PrimaryColor::orderBy("name")->get();
        $productExamples = !$attribute ? collect() : Product::with("productFamily")
            ->where("variant_name", $attribute?->name)
            ->get()
            ->groupBy(["productFamily.source"]);

        return view("admin.main-attribute", compact("attribute", "primaryColors", "productExamples"));
    }
    public function mainAttributePrune()
    {
        if (!userIs("Administrator")) abort(403);
        $used_attrs = Product::pluck("variant_name")->unique()->toArray();
        MainAttribute::all()
            ->filter(fn ($attr) => !in_array($attr->name, $used_attrs))
            ->each(fn ($attr) => $attr->delete());
        return back()->with("success", "Nieużywane cechy podstawowe zostały usunięte");
    }
    public function primaryColorsList()
    {
        $data = PrimaryColor::orderBy("name")->get();
        return view("admin.primary-colors", compact(
            "data",
        ));
    }
    public function primaryColorEdit($id = null)
    {
        $attribute = PrimaryColor::find($id);
        return view("admin.primary-color", compact(
            "attribute",
        ));
    }

    public function suppliers()
    {
        if (!userIs("Edytor")) abort(403);

        $sync_suppliers = ProductSynchronization::orderBy("supplier_name")->get();
        $custom_suppliers = CustomSupplier::orderBy("name")->get();

        return view("admin.suppliers.list", compact(
            "sync_suppliers",
            "custom_suppliers",
        ));
    }
    public function supplierEdit(?int $id = null)
    {
        if (!userIs("Edytor")) abort(403);

        $supplier = ($id) ? CustomSupplier::findOrFail($id) : null;

        return view("admin.suppliers.edit", compact(
            "supplier",
        ));
    }

    public function synchronizations()
    {
        if (!userIs("Administrator")) abort(403);

        return view("admin.synchronizations");
    }
    public function synchronizationEdit(string $supplier_name)
    {
        if (!userIs("Administrator")) abort(403);

        $synchronization = ProductSynchronization::findOrFail($supplier_name);
        $quicknessPriorities = array_flip(ProductSynchronization::QUICKNESS_LEVELS);
        $modulePriorities = array_flip(ProductSynchronization::ENABLED_LEVELS);

        return view("admin.synchronization.edit", compact(
            "synchronization",
            "quicknessPriorities",
            "modulePriorities",
        ));
    }
    #endregion

    #region files
    public function files()
    {
        $path = request("path") ?? "";

        $directories = Storage::disk("public")->directories($path);
        $files = collect(Storage::disk("public")->files($path))
            ->filter(fn ($file) => !Str::contains($file, ".git"))
            // ->sortByDesc(fn ($file) => Storage::lastModified($file) ?? 0)
        ;

        return view("admin.files.list", compact(
            "files",
            "directories",
        ));
    }

    public function filesUpload(Request $rq)
    {
        foreach ($rq->file("files") as $file) {
            $file->storePubliclyAs(
                $rq->path,
                $rq->get("force_file_name") ?: $file->getClientOriginalName(),
                "public",
            );
        }

        return back()->with("success", "Dodano");
    }

    public function filesDownload(Request $rq)
    {
        return Storage::download("public/".$rq->file);
    }

    public function filesDelete(Request $rq)
    {
        Storage::disk("public")->delete($rq->file);
        return back()->with("success", "Usunięto");
    }

    public function filesSearch()
    {
        $files = collect(Storage::disk("public")->allFiles())
            ->filter(fn($file) => Str::contains($file, request("q")));

        return view("admin.files.search", compact(
            "files",
        ));
    }

    public function folderCreate(Request $rq)
    {
        $path = request("path") ?? "";
        Storage::disk("public")->makeDirectory($path . "/" . $rq->name);
        return redirect()->route("files", ["path" => $path])->with("success", "Folder utworzony");
    }

    public function folderDelete(Request $rq)
    {
        $path = request("path") ?? "";
        Storage::disk("public")->deleteDirectory($path);
        return redirect()->route("files", ["path" => Str::contains($path, '/') ? Str::beforeLast($path, '/') : null])->with("success", "Folder usunięty");
    }
    #endregion

    #region updaters
    public function updateProducts(Request $rq)
    {
        if (!userIs("Edytor")) abort(403);

        $form_data = prepareFormData($rq, [
            "enable_discount" => "bool",
            "price" => "number",
            "image_urls" => "json",
            "extra_filtrables" => "json",
        ]);

        $form_data["id"] ??= $form_data["product_family_id"] . Product::newCustomProductVariantSuffix($form_data["product_family_id"]);
        // translate tab tables contents (labels, values)
        $form_data["tabs"] = json_decode($rq->tabs, true) ?? null;
        // translate sizes
        $form_data["sizes"] = empty($rq->sizes["size_names"])
            ? null
            : collect($rq->sizes["size_names"])
                ->map(fn ($size, $i) => [
                    "size_name" => $size,
                    "size_code" => $rq->sizes["size_codes"][$i],
                    "full_sku" => $rq->sizes["full_skus"][$i],
                ]);
        $form_data["extra_filtrables"] = empty($form_data["extra_filtrables"])
            ? null
            : array_map(fn ($fs) => explode("|", $fs), $form_data["extra_filtrables"]);
        if (Str::of($form_data["description"])->stripTags()->trim("&nbsp;")->trim()->isEmpty()) $form_data["description"] = null;

        if ($rq->mode == "save") {
            $product = Product::updateOrCreate(["id" => $rq->id], $form_data);

            // foreach (["images", "thumbnails"] as $type) {
            //     foreach (Storage::allFiles("public/products/$product->id/$type") as $image) {
            //         if (!in_array(env("APP_URL") . Storage::url($image), $$type ?? [])) {
            //             Storage::delete($image);
            //         }
            //     }
            //     foreach ($rq->file("new".ucfirst($type)) ?? [] as $image) {
            //         $image->storePubliclyAs("products/$product->id/$type", $image->getClientOriginalName(), "public");
            //     }
            // }

            return redirect(route("products-edit", ["id" => $product->id]))->with("success", "Produkt został zapisany");
        } else if ($rq->mode == "delete") {
            $product = Product::find($rq->id);
            $product->delete();
            Storage::deleteDirectory("public/products/$rq->id");
            return redirect(route("products-edit-family", ['id' => $rq->product_family_id]))->with("success", "Produkt został usunięty");
        } else {
            abort(400, "Updater mode is missing or incorrect");
        }
    }

    public function updateProductFamilies(Request $rq)
    {
        $form_data = prepareFormData($rq, [
            "image_urls" => "json",
        ]);

        $form_data["id"] ??= ProductFamily::newCustomProductId();
        $form_data["original_sku"] ??= $form_data["id"];
        // translate tab tables contents (labels, values)
        $form_data["tabs"] = json_decode($rq->tabs, true) ?? null;
        $form_data["alt_attributes"]["variants"] = json_decode($rq->alt_attributes["variants"] ?? "[]", true);
        if (!$rq->has("enable_alt_attributes")) $form_data["alt_attributes"] = null;

        if (is_numeric($form_data["source"])) {
            $form_data["source"] = ProductFamily::CUSTOM_PRODUCT_GIVEAWAY . $form_data["source"];
        }
        if (Str::of($form_data["description"])->stripTags()->trim("&nbsp;")->trim()->isEmpty()) $form_data["description"] = null;

        if ($rq->mode == "save") {
            $family = ProductFamily::updateOrCreate(["id" => $rq->id], $form_data);

            // foreach (["images", "thumbnails"] as $type) {
            //     foreach (Storage::allFiles("public/products/$family->id/$type") as $image) {
            //         if (!in_array(env("APP_URL") . Storage::url($image), $$type)) {
            //             Storage::delete($image);
            //         }
            //     }
            //     foreach ($rq->file("new".ucfirst($type)) ?? [] as $image) {
            //         $image->storePubliclyAs("products/$family->id/$type", $image->getClientOriginalName(), "public");
            //     }
            // }

            if ($family->products->count() == 0) {
                Product::create([
                    "id" => $family->id.Product::newCustomProductVariantSuffix($family->id),
                    "name" => $family->name,
                    "product_family_id" => $family->id,
                ]);
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

    public function updateMainAttributes(Request $rq)
    {
        if (!userIs("Edytor")) abort(403);

        $form_data = $rq->except(["_token", "mode", "id"]);
        $form_data["color"] ??= "";
        if ($rq->mode == "save") {
            $attribute = MainAttribute::updateOrCreate(["id" => $rq->id], $form_data);
            return redirect(route("main-attributes-edit", ["id" => $attribute->id]))->with("success", "Atrybut został zapisany");
        } else if ($rq->mode == "delete") {
            MainAttribute::find($rq->id)->delete();
            MainAttribute::where("color", "@".$rq->id)->update(["color" => ""]);
            return redirect(route("attributes"))->with("success", "Atrybut został usunięty");
        } else {
            abort(400, "Updater mode is missing or incorrect");
        }
    }

    public function primaryColorProcess(Request $rq)
    {
        $form_data = $rq->except(["_token", "mode"]);
        $form_data["color"] ??= "";

        if ($rq->mode == "save") {
            $color = PrimaryColor::updateOrCreate(["id" => $rq->id], $form_data);
            return redirect(route("primary-color-edit", ["id" => $color->id]))->with("success", "Kolor został zapisany");
        } else if ($rq->mode == "delete") {
            PrimaryColor::find($rq->id)->delete();
            return redirect(route("primary-colors-list"))->with("success", "Kolor został usunięty");
        } else {
            abort(400, "Updater mode is missing or incorrect");
        }
    }

    public function updateSuppliers(Request $rq)
    {
        if (!userIs("Edytor")) abort(403);

        $form_data = $rq->except(["_token", "mode", "id"]);
        $form_data["categories"] = $rq->categories ?? [];

        if ($rq->mode == "save") {
            $supplier = CustomSupplier::updateOrCreate(["id" => $rq->id], $form_data);
            return redirect(route("suppliers-edit", ["id" => $supplier->id]))->with("success", "Dostawca zaktualizowany");
        } else if ($rq->mode == "delete") {
            CustomSupplier::find($rq->id)->delete();
            return redirect(route("suppliers"))->with("success", "Dostawca usunięty");
        } else {
            abort(400, "Updater mode is missing or incorrect");
        }
    }

    public function updateSynchronizations(Request $rq)
    {
        if (!userIs("Administrator")) abort(403);

        $form_data = $rq->except(["_token"]);
        $synch = ProductSynchronization::find($rq->supplier_name)
            ->update($form_data);
        return redirect()->route("synchronizations")->with("success", "Synchronizacja poprawiona");
    }
    #endregion

    #region product specs import
    public function productImportSpecs(string $entity_name, string $id): View
    {
        $entity_name = "App\\Models\\$entity_name";
        $entity = $entity_name::find($id);

        return view("admin.product.import-specs", compact(
            "entity",
        ));
    }

    public function productImportSpecsProcess(Request $rq)
    {
        $entity = $rq->entity_name::find($rq->id);

        if ($rq->mode == "process") {
            $specs_raw = $rq->specs_raw;
            $specs = Str::of($specs_raw)
                ->replace(["<figure class=\"table\">", "</figure>"], "");

            $html = new DOMDocument;
            $specs = mb_convert_encoding($specs, "HTML-ENTITIES", "UTF-8");
            $html->loadHTML($specs);

            $xml = new SimpleXMLElement(mb_convert_encoding($html->saveXML(), 'UTF-8', 'HTML-ENTITIES'));
            $rows = $xml->xpath("//tr");
            $cells = [];
            foreach ($rows as $row) {
                if ($row->xpath("td[@colspan=2]")) {
                    // header - add new cell
                    $cells[] = [
                        "type" => "table",
                        "heading" => (string) $row->xpath("td/strong")[0],
                        "content" => [],
                    ];
                } else {
                    // normal row - add to previous cell's content
                    $content_row = collect($row->xpath("td"))
                        ->map(fn ($cell) => Str::of($cell->xpath("strong")
                            ? $cell->strong
                            : $cell
                        )->replace("\u{A0}", "")->trim()->toString());
                    $cells[count($cells) - 1]["content"][$content_row[0]] = $content_row[1];
                }
            }

            $spec_tabs = [[
                "name" => "Specyfikacja",
                "cells" => $cells,
            ]];
            $tabs = array_merge($entity->tabs ?? [], $spec_tabs);

            return view("admin.product.import-specs", compact(
                "entity",
                "specs_raw",
                "tabs",
            ));
        }

        if ($rq->mode == "save") {
            $entity->update([
                "tabs" => json_decode($rq->tabs, true),
            ]);

            return redirect(route(
                Str::of($entity::class)->contains('ProductFamily') ? 'products-edit-family' : 'products-edit',
                ["id" => $entity->id]
            ))->with("success", "Specyfikacja zaktualizowana");
        }

        abort(400, "Updater mode is missing or incorrect");
    }
    #endregion

    #region alt attributes
    public function aatrEdit(?AltAttribute $attribute = null): View
    {
        return view("admin.attributes.alt.edit", compact(
            "attribute",
        ));
    }

    public function aatrProcess(Request $rq): RedirectResponse
    {
        if (!userIs("Edytor")) abort(403);

        $form_data = $rq->except(["_token", "mode", "id"]);
        $form_data["large_tiles"] = $rq->has("large_tiles");
        $form_data["variants"] = json_decode($rq->variants ?? "[]", true);

        if ($rq->mode == "save") {
            $attribute = AltAttribute::updateOrCreate(["id" => $rq->id], $form_data);
            return redirect(route("alt-attributes-edit", ["attribute" => $attribute]))->with("success", "Cecha zaktualizowana");
        } else if ($rq->mode == "delete") {
            AltAttribute::find($rq->id)->delete();
            return redirect(route("attributes"))->with("success", "Cecha usunięta");
        } else {
            abort(400, "Updater mode is missing or incorrect");
        }
    }
    #endregion

    #region product generate variants
    public function productGenerateVariants(string $family_id)
    {
        if (!userIs("Edytor")) abort(403);

        $family = ProductFamily::findOrFail($family_id);

        $variants = (empty($family->alt_attributes))
            ? PrimaryColor::orderBy("name")->get()
            : $family->alt_attribute_tiles;

        return view("admin.product.generate-variants", compact(
            "family_id",
            "family",
            "variants",
        ));
    }

    public function productGenerateVariantsProcess(Request $rq)
    {
        if (!userIs("Edytor")) abort(403);

        $family = ProductFamily::findOrFail($rq->family_id);
        Product::where("product_family_id", $rq->family_id)->delete();

        foreach ($rq->variants as $variant_name) {
            Product::create([
                "id" => $family->id.Product::newCustomProductVariantSuffix($family->id),
                "name" => $family->name,
                "product_family_id" => $family->id,
                "variant_name" => $variant_name,
            ]);
        }

        return redirect()->route("products-edit-family", ["id" => $family->prefixed_id])->with("success", "Warianty wygenerowane");
    }
    #endregion

    #region helpers
    /**
     * takes tab builder data from request and renders the editor
     */
    public function prepareProductTabs(Request $rq): View
    {
        $tabs = json_decode($rq->tabs, true);
        $editable = ($rq->get("_model", "App\\Models\\Product"))::find($rq->get("id"))->is_custom ?? true;
        return view("components.product.tabs-editor", compact("tabs", "editable"));
    }

    public function prepareSupplierCategories(Request $rq): View
    {
        $items = collect($rq->categories)->sort();
        return view("components.suppliers.categories-editor", compact("items"));
    }
    public function getSupplierByName(string $supplier_name): JsonResponse
    {
        $supplier = CustomSupplier::where("name", $supplier_name)->first();
        $items = $supplier->categories;
        $items = collect($items)->combine($items);
        $value = null;

        return response()->json([
            "supplier" => $supplier,
            "categoriesSelector" => view("components.suppliers.categories-selector", compact("items", "value"))->render(),
        ]);
    }

    public function getSupplier(int $id): JsonResponse
    {
        $supplier = CustomSupplier::find($id);
        $items = $supplier->categories;
        $items = collect($items)->combine($items);
        $value = null;

        return response()->json([
            "supplier" => $supplier,
            "categoriesSelector" => view("components.suppliers.categories-selector", compact("items", "value"))->render(),
        ]);
    }
    #endregion

    #region synchronization
    public function getSynchData(Request $rq)
    {
        $synchronizations = ProductSynchronization::ordered()->get()
            ->groupBy("quickness_priority");

        return response()->json([
            "table" => view("components.synchronizations.table", compact("synchronizations"))->render(),
            "queue" => view("components.synchronizations.queue")->render(),
        ]);
    }
    public function synchMod(string $action, Request $rq)
    {
        if (!userIs("Administrator")) abort(403);

        switch ($action) {
            case "enable":
                ProductSynchronization::whereRaw(empty($rq->supplier_name) ? "true" : "supplier_name = '$rq->supplier_name'")
                    ->update(
                        ($rq->mode)
                            ? [$rq->mode."_import_enabled" => $rq->enabled]
                            : [
                                "product_import_enabled" => $rq->enabled,
                                "stock_import_enabled" => $rq->enabled,
                                "marking_import_enabled" => $rq->enabled,
                            ]
                    );
                return response()->json("Status synchronizacji został zmieniony");

            case "reset":
                if (empty($rq->supplier_name)) {
                    foreach (ProductSynchronization::all() as $integrator) {
                        $integrator->update([
                            "product_import_enabled" => true,
                            "stock_import_enabled" => true,
                            "marking_import_enabled" => true,
                            "product_import" => [
                                "synch_status" => null,
                                "current_external_id" => null,
                                "progress" => 0,
                                "last_sync_started_at" => null,
                                "last_sync_zero_at" => null,
                                "last_sync_completed_at" => null,
                                "last_sync_zero_to_full" => null,
                            ],
                            "stock_import" => [
                                "synch_status" => null,
                                "current_external_id" => null,
                                "progress" => 0,
                                "last_sync_started_at" => null,
                                "last_sync_zero_at" => null,
                                "last_sync_completed_at" => null,
                                "last_sync_zero_to_full" => null,
                            ],
                            "marking_import" => [
                                "synch_status" => null,
                                "current_external_id" => null,
                                "progress" => 0,
                                "last_sync_started_at" => null,
                                "last_sync_zero_at" => null,
                                "last_sync_completed_at" => null,
                                "last_sync_zero_to_full" => null,
                            ],
                        ]);
                        Cache::forget("synch_".strtolower($integrator->supplier_name)."_in_progress");
                    }
                } else {
                    ProductSynchronization::where("supplier_name", $rq->supplier_name)->update([
                        "product_import_enabled" => true,
                        "stock_import_enabled" => true,
                        "marking_import_enabled" => true,
                        "product_import" => [
                            "synch_status" => null,
                            "current_external_id" => null,
                            "progress" => 0,
                            "last_sync_started_at" => null,
                            "last_sync_zero_at" => null,
                            "last_sync_completed_at" => null,
                            "last_sync_zero_to_full" => null,
                        ],
                        "stock_import" => [
                            "synch_status" => null,
                            "current_external_id" => null,
                            "progress" => 0,
                            "last_sync_started_at" => null,
                            "last_sync_zero_at" => null,
                            "last_sync_completed_at" => null,
                            "last_sync_zero_to_full" => null,
                        ],
                        "marking_import" => [
                            "synch_status" => null,
                            "current_external_id" => null,
                            "progress" => 0,
                            "last_sync_started_at" => null,
                            "last_sync_zero_at" => null,
                            "last_sync_completed_at" => null,
                            "last_sync_zero_to_full" => null,
                        ],
                    ]);
                    Cache::forget("synch_".strtolower($rq->supplier_name)."_in_progress");
                }
                return response()->json("Synchronizacja została zresetowana");
        }
    }
    #endregion
}
