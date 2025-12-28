<?php

namespace App\Http\Controllers;

use App\Jobs\RefreshProductsJob;
use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\ProductTag;
use App\Models\Setting;
use App\Models\Supervisor;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdminController extends Controller
{
    #region pages
    public function productEdit(?string $id = null)
    {
        $family = ($id) ? Product::familyByPrefixedId($id)->get() : null;
        $product = $family?->first();
        $tags = ProductTag::ordered()->get();
        $potential_related_products = Product::all()
            ->groupBy("product_family_id")
            ->map(function ($variants, $pf) {
                $first_variant = $variants->first();
                return [
                    "id" => $pf,
                    "name" => $first_variant->family_name,
                    "text" => "$first_variant->family_name ($first_variant->family_prefixed_id)",
                    "thumbnail" => $first_variant->image_urls->first(),
                ];
            });

        return view("admin.product", compact(
            "family",
            "product",
            "tags",
            "potential_related_products",
        ));
    }
    #endregion

    public function productImportInit()
    {
        $data = Http::get(env("MAGAZYN_API_URL") . "suppliers")->collect()
            ->pluck("source", "name")
            ->sortKeys();

        return view("admin.product-import", compact("data"));
    }
    public function productImportFetch(Request $rq)
    {
        [$source, $category, $query] = [$rq->source, $rq->category, $rq->get("query")];

        $data = ($category || $query)
            ? Http::post(env("MAGAZYN_API_URL") . "products/by", compact(
                "source",
                "category",
                "query",
            ))->collect()
                ->sortBy(fn ($pf) => collect($pf["products"])->avg("price"))
            : Http::post(env("MAGAZYN_API_URL") . "products/by", compact(
                "source",
            ))->collect()
                ->mapWithKeys(fn ($p) => [$p["original_category"] => $p["original_category"]])
                ->sort();

        return view("admin.product-import", compact("data", "source", "category", "query"));
    }
    public function productImportImport(Request $rq)
    {
        $families = Http::post(env("MAGAZYN_API_URL") . "products/by/ids", [
            "ids" => $rq->ids,
            "families" => true,
        ])
            ->collect();
        $categories = array_filter($rq->categories ?? []);

        foreach ($families as $family) {
            foreach ($family["products"] as $product) {
                $created_product = Product::updateOrCreate(["id" => $product["id"]], [
                    "product_family_id" => $product["product_family_id"],
                    "front_id" => $product["front_id"],
                    "visible" => $rq->get("visible") ?? 2,
                    "name" => $product["name"],
                    "subtitle" => $product["product_family"]["subtitle"],
                    "family_name" => $product["product_family"]["name"],
                    "query_string" => implode(" ", [
                        $product["front_id"],
                        $product["name"],
                        $product["variant_data"]["name"] ?? null,
                    ]),
                    "description" => $product["combined_description"] ?? null,
                    "specification" => $product["specification"] ?? null,
                    "description_label" => $product["product_family"]["description_label"],
                    "images" => $product["combined_images"] ?? null,
                    "thumbnails" => $product["combined_thumbnails"] ?? null,
                    "color" => $product["variant_data"],
                    "sizes" => $product["sizes"],
                    "extra_filtrables" => $product["extra_filtrables"],
                    "brand_logo" => $product["brand_logo"],
                    "original_sku" => $product["original_sku"],
                    "price" => $product["show_price"] ? ($product["price"] * ($product["ofertownik_price_multiplier"] ?? 1)) : null,
                    "tabs" => $product["combined_tabs"] ?? null,
                    "is_synced_with_magazyn" => true,
                ]);

                $created_product->categories()->sync($categories);
            }
        }

        return redirect()->route("admin.model.list", ["model" => "products"])->with("toast", ["success", "Produkty zostały zaimportowane"]);
    }

    #region product refresh
    public function productImportRefresh()
    {
        RefreshProductsJob::dispatch()->delay(now()->addMinutes(1));
        RefreshProductsJob::status([
            "status" => "oczekuje",
            "current_id" => null,
            "progress" => 0,
        ]);

        return back()->with("toast", ["success", "Wymuszono odświeżenie"]);
    }

    public function productImportRefreshStatus(): View
    {
        $refreshData = json_decode(
            Storage::disk("public")->get("meta/refresh-products-status.json"),
            true
        );
        $unsynced = Product::where("is_synced_with_magazyn", false)->get()
            ->sortBy("front_id");

        return view("components.product-refresh-status", compact(
            "refreshData",
            "unsynced",
        ));
    }

    public function productUnsyncedList()
    {
        $unsynced = Product::where("is_synced_with_magazyn", false)->get();

        return view("admin.products.unsynced", compact("unsynced"));
    }

    public function productUnsyncedDelete(Request $rq)
    {
        Product::whereIn("id", $rq->ids)->delete();
        return redirect()->route("admin.model.list", ["model" => "products"])->with("toast", ["success", "Wybrane produkty zostały usunięte z Ofertownika"]);
    }
    #endregion

    #region helpers product tags
    public function productTagUpdateForProducts(Request $rq)
    {
        $product = Product::where("product_family_id", $rq->product_family_id)->first();
        $edited_tag = $product->tags->firstWhere("details.id", $rq->details_id);

        if ($product->tags->firstWhere("id", $rq->tag_id)) {
            return back()->with("toast", ["error", "Nie można dodać ponownie tego samego taga"]);
        }

        if ($edited_tag) {
            $edited_tag->details->update([
                "product_tag_id" => $rq->tag_id,
                "start_date" => $rq->start_date,
                "end_date" => $rq->end_date,
                "disabled" => $rq->has("disabled"),
            ]);
        } else {
            $product->tags()->attach(
                $rq->tag_id,
                [
                    "start_date" => $rq->start_date,
                    "end_date" => $rq->end_date,
                    "disabled" => $rq->has("disabled"),
                ]
            );
        }

        return back()->with("toast", ["success", "Tag zaktualizowany"]);
    }
    #endregion

    #region product ordering
    public function productOrderingManage(?Category $category): View
    {
        return view("admin.product-ordering", compact(
            "category",
        ));
    }

    public function productOrderingSubmit(Request $rq): RedirectResponse
    {
        $orderings = collect($rq->ordering);
        $products = Product::whereIn("product_family_id", $orderings->keys())
            ->get();

        Category::find($rq->category_id)->products()->sync($products->mapWithKeys(fn ($p) => [
            $p->id => ["ordering" => $orderings->get($p->product_family_id)],
        ]));

        return back()->with("toast", ["success", "Zapisano"]);
    }
    #endregion

    #region categories
    public function categoryUpdateOrdering(Request $rq): RedirectResponse
    {
        Category::find($rq->id)->update(["ordering" => $rq->ordering]);

        return back()->with("toast", ["success", "Priorytety zaktualizowane"]);
    }

    public function productCategoryAssignmentManage(?Category $category): View
    {
        return view("admin.product-category-assignment", compact(
            "category",
        ));
    }

    public function productCategoryAssignmentSubmit(Request $rq): RedirectResponse
    {
        Product::whereIn("product_family_id", $rq->ids)->get()->each(fn ($p) => ($rq->input("mode") == "attach")
            ? $p->categories()->attach($rq->input("categories"))
            : $p->categories()->sync($rq->input("categories"))
        );

        return back()->with("toast", ["success", "Zapisano"]);
    }
    #endregion

    #region updaters
    public function updateProducts(Request $rq)
    {
        $form_data = $rq->except(["_token", "mode", "id"]);
        $categories = array_filter($form_data["categories"] ?? []);
        foreach ([
            "hide_family_sku_on_listing",
            "show_price",
        ] as $boolean) {
            $form_data[$boolean] = $rq->has($boolean);
        }

        $is_synced_with_magazyn = true;
        $magazyn_data = Http::get(env("MAGAZYN_API_URL") . "products/$rq->id/1")
            ->json();
        if (!$magazyn_data) {
            $is_synced_with_magazyn = false;
            $magazyn_data = Product::where("product_family_id", $rq->id)->get()->map(fn ($p) => [
                "id" => $p->id,
                "name" => $p->name,
                "subtitle" => $p->subtitle,
                "description" => $p->description,
                "description_label" => $p->description_label,
                "specification" => $p->specification,
                "family_name" => $p->family_name,
                "product_family_id" => $p->product_family_id,
                "variant_data" => $p->color,
                "image_urls" => $p->image_urls,
                "images" => $p->images,
                "thumbnail_urls" => $p->image_urls,
                "thumbnails" => $p->thumbnails,
                "original_sku" => $p->original_sku,
                "price" => $p->price,
                "show_price" => true,
                "tabs" => $p->tabs,
                "sizes" => $p->sizes,
                "brand_logo" => $p->brand_logo,
                "extra_filtrables" => $p->extra_filtrables,
                "front_id" => $p->front_id,
                "hide_family_sku_on_listing" => $p->hide_family_sku_on_listing,
                "is_synced_with_magazyn" => $is_synced_with_magazyn,
                "prefixed_id" => $p->family_prefixed_id,
            ]);
        }

        if ($rq->mode == "save") {
            $family = Product::where("product_family_id", $rq->id)->get();

            foreach ($magazyn_data as $magazyn_product) {
                foreach (["images", "thumbnails", "description", "tabs"] as $key) {
                    $magazyn_product[$key] = $magazyn_product["combined_$key"] ?? $magazyn_product[$key];
                }
                $form_data["front_id"] = $magazyn_product["front_id"];
                $form_data["description_label"] = $magazyn_product["product_family"]["description_label"]
                    ?? $magazyn_product["description_label"] ?? null;
                $form_data["color"] = $magazyn_product["variant_data"];
                unset($magazyn_product["color"]);
                $form_data["family_name"] = $magazyn_product["product_family"]["name"]
                    ?? $magazyn_product["family_name"];
                $form_data["subtitle"] = $magazyn_product["product_family"]["subtitle"]
                    ?? $magazyn_product["subtitle"] ?? null;
                $form_data["price"] = $magazyn_product["show_price"] ? ($magazyn_product["price"] * ($magazyn_product["ofertownik_price_multiplier"] ?? 1)) : null;
                $form_data["query_string"] = implode(" ", [
                    $magazyn_product["front_id"],
                    $magazyn_product["name"],
                    $magazyn_product["variant_data"]["name"] ?? null,
                ]);
                $form_data["is_synced_with_magazyn"] = $is_synced_with_magazyn;

                $ofertownik_product = Product::updateOrCreate(["id" => $magazyn_product["id"]], array_merge($magazyn_product, $form_data));
                $ofertownik_product->categories()->sync($categories);
            }

            if ($is_synced_with_magazyn) {
                foreach ($family->whereNotIn("id", array_map(fn ($p) => $p["id"], $magazyn_data)) as $ofertownik_product) {
                    $ofertownik_product->delete();
                }
            }

            return redirect(route("products-edit", ["id" => $magazyn_data[0]["product_family"]["prefixed_id"] ?? $magazyn_data[0]["prefixed_id"]]))->with("toast", ["success", "Produkt został zapisany"]);
        } else if ($rq->mode == "delete") {
            Product::where("product_family_id", $rq->id)->delete();
            return redirect(route("products"))->with("toast", ["success", "Produkt został usunięty"]);
        } else if (Str::startsWith($rq->mode, "delete_tag")) {
            $product = Product::where("product_family_id", $rq->id)->first();
            $product->tags()->detach($rq->tag_id);
            return redirect(route("products-edit", ["id" => $product->family_prefixed_id]))->with("toast", ["success", "Tag został usunięty"]);
        } else {
            abort(400, "Updater mode is missing or incorrect");
        }
    }
    #endregion

    #region helpers
    #endregion
}
