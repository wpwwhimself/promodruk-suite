<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Supervisor;
use App\Models\TopNavPage;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public static $pages = [
        ["Ogólne", "dashboard"],
        ["Strony górne", "top-nav-pages"],
        ["Kategorie", "categories"],
        ["Produkty", "products"],
        ["Pliki", "files"],
    ];

    public static $updaters = [
        "settings",
        "logo",
        "welcome-text",
        "top-nav-pages",
        "categories",
        "products",
        "files",
    ];

    /////////////// pages ////////////////

    public function dashboard()
    {
        $general_settings = Setting::where("group", "general")->get();
        [$welcome_text_content, $welcome_text_visible] = Setting::where("group", "welcome_text")->get();
        $queries_settings = Setting::where("group", "queries")->get();
        $showcase_settings = Setting::where("group", "showcase")->get();
        $auxiliary_products_visibility_settings = Setting::where("group", "auxiliary_products_visibility")->get();

        $supervisors = Supervisor::all();

        return view("admin.dashboard", compact(
            "general_settings",
            "welcome_text_content",
            "welcome_text_visible",
            "queries_settings",
            "showcase_settings",
            "auxiliary_products_visibility_settings",
            "supervisors",
        ));
    }

    public function topNavPages()
    {
        $perPage = request("perPage", 100);
        $sortBy = request("sortBy", "name");

        $pages = TopNavPage::all()
            ->sort(fn ($a, $b) => sortByNullsLast(
                Str::afterLast($sortBy, "-"),
                $a, $b,
                Str::startsWith($sortBy, "-")
            ));

        $pages = new LengthAwarePaginator(
            $pages->slice($perPage * (request("page", 1) - 1), $perPage),
            $pages->count(),
            $perPage,
            request("page", 1),
            ["path" => ""]
        );

        return view("admin.top-nav-pages", compact(
            "pages",
            "perPage",
            "sortBy",
        ));
    }
    public function topNavPageEdit(int $id = null)
    {
        $page = ($id) ? TopNavPage::findOrFail($id) : null;

        return view("admin.top-nav-page", compact(
            "page"
        ));
    }

    public function categories()
    {
        $perPage = request("perPage", 100);
        $sortBy = request("sortBy", "ordering");

        $categories = Category::all()
            ->sort(fn ($a, $b) => $a[$sortBy] <=> $b[$sortBy])
            ->filter(fn ($cat) => $cat->parent_id == (request("filters") ? request("filters")["cat_parent_id"] ?? null : null));

        $categories = new LengthAwarePaginator(
            $categories->slice($perPage * (request("page", 1) - 1), $perPage),
            $categories->count(),
            $perPage,
            request("page", 1),
            ["path" => ""]
        );

        $catsForFiltering = Category::all()->pluck("id", "name");

        return view("admin.categories", compact(
            "categories",
            "perPage",
            "sortBy",
            "catsForFiltering",
        ));
    }
    public function categoryEdit(int $id = null)
    {
        $category = ($id) ? Category::findOrFail($id) : null;

        $parent_categories_available = Category::all()
            ->reject(fn ($cat) => $cat->id === $id);
        if ($category) {
            $parent_categories_available = $parent_categories_available->filter(
                fn ($cat) => !$category->all_children->contains(fn ($ccat) => $ccat->id == $cat->id)
            );
        }
        $parent_categories_available = $parent_categories_available->mapWithKeys(
            fn ($cat) => [str_repeat("- ", $cat->depth) . $cat->name => $cat->id]
        );

        return view("admin.category", compact(
            "category",
            "parent_categories_available",
        ));
    }

    public function products()
    {
        $perPage = request("perPage", 100);
        $sortBy = request("sortBy", "name");

        $products = Product::where("name", "like", "%".request("query")."%")
            ->orWhere("id", "like", "%".request("query")."%")
            ->orWhere("description", "like", "%".request("query")."%")
            ->get()
            ->sort(fn ($a, $b) => $a[$sortBy] <=> $b[$sortBy])
            ->filter(fn ($prod) => (isset(request("filters")["cat_id"]))
                ? in_array(request("filters")["cat_id"], $prod->categories->pluck("id")->toArray())
                : true
            )
            ->filter(fn ($prod) => (isset(request("filters")["visibility"]))
                ? $prod->visible == request("filters")["visibility"]
                : true
            );

        $products = new LengthAwarePaginator(
            $products->slice($perPage * (request("page", 1) - 1), $perPage),
            $products->count(),
            $perPage,
            request("page", 1),
            ["path" => ""]
        );

        $catsForFiltering = Category::whereNull("parent_id")
            ->orderBy("ordering")
            ->orderBy("name")
            ->get()
            ->flatMap(fn ($cat) => [$cat, ...$cat->children])
            ->mapWithKeys(function ($cat) {
                $name = $cat->name_for_list;
                if ($cat->products->count() > 0) $name .= " (" . $cat->products->count() . ")";
                return [$name => $cat->id];
            })
            ->toArray();

        return view("admin.products", compact(
            "products",
            "perPage",
            "sortBy",
            "catsForFiltering",
        ));
    }
    public function productEdit(string $id = null)
    {
        $product = ($id) ? Product::findOrFail($id) : null;
        return view("admin.product", compact(
            "product",
        ));
    }

    public function productImportInit()
    {
        $data = Http::get(env("MAGAZYN_API_URL") . "suppliers")->collect()
            ->mapWithKeys(fn ($s) => is_array($s["prefix"])
                ? ["$s[name] (" . implode("/", $s["prefix"]) . ")" => implode(";", $s["prefix"])]
                : ["$s[name] ($s[prefix])" => $s["prefix"]]
            )
            ->sort();

        return view("admin.product-import", compact("data"));
    }
    public function productImportFetch(Request $rq)
    {
        [$supplier, $category, $query] = [$rq->supplier, $rq->category, $rq->get("query")];

        $data = ($category || $query)
            ? Http::get(env("MAGAZYN_API_URL") . "products/by/$supplier/".($category ?? '---')."/$query")->collect()
            : Http::get(env("MAGAZYN_API_URL") . "products/by/$supplier")->collect()
                ->mapWithKeys(fn ($p) => [$p["original_category"] => $p["original_category"]])
                ->sort();

        return view("admin.product-import", compact("data", "supplier", "category", "query"));
    }
    public function productImportImport(Request $rq)
    {
        $products = Http::post(env("MAGAZYN_API_URL") . "products/by/ids", [
            "ids" => $rq->ids
        ])
            ->collect();
        $categories = array_filter(explode(",", $rq->categories ?? ""));

        foreach ($products as $product) {
            $product = Product::updateOrCreate(["id" => $product["id"]], [
                "product_family_id" => $product["product_family_id"],
                "visible" => $rq->get("visible") ?? 2,
                "name" => $product["name"],
                "description" => $product["description"],
                "images" => $product["images"],
                "thumbnails" => $product["thumbnails"],
                "color" => $product["color"],
                "attributes" => $product["attributes"],
                "original_sku" => $product["original_sku"],
                "price" => $product["price"],
                "tabs" => $product["tabs"],
            ]);

            $product->categories()->sync($categories);
        }

        return redirect()->route("products")->with("success", "Produkty zostały zaimportowane");
    }

    public function productImportRefresh()
    {
        [
            "products" => $products,
            "missing" => $missing,
        ] = Http::post(env("MAGAZYN_API_URL") . "products/for-refresh", [
            "ids" => Product::all()->pluck("id"),
        ])->collect();

        foreach ($products as $product) {
            $product = Product::updateOrCreate(["id" => $product["id"]], [
                "product_family_id" => $product["product_family_id"],
                "name" => $product["name"],
                "description" => $product["description"],
                "images" => $product["images"],
                "thumbnails" => $product["thumbnails"],
                "color" => $product["color"],
                "attributes" => $product["attributes"],
                "original_sku" => $product["original_sku"],
                "price" => $product["price"],
                "tabs" => $product["tabs"],
            ]);
        }
        $out = "Produkty zostały odświeżone";
        if (count($missing) > 0) {
            Product::whereIn("id", $missing)->delete();
            $out .= ", usunięto " . count($missing) . " nieistniejących";
        }

        return redirect()->route("products")->with("success", $out);
    }

    public function files()
    {
        $path = request("path") ?? "/";

        $directories = Storage::directories($path);
        $files = collect(Storage::files($path))
            ->filter(fn ($file) => !Str::contains($file, ".git"));

        return view("admin.files", compact(
            "files",
            "directories",
        ));
    }
    public function filesUpload(Request $rq)
    {
        foreach ($rq->file("files") as $file) {
            $file->storePubliclyAs(
                $rq->path,
                $file->getClientOriginalName()
            );
        }

        return back()->with("success", "Dodano");
    }
    public function filesDownload(Request $rq)
    {
        return Storage::download($rq->file);
    }
    public function filesDelete(Request $rq)
    {
        Storage::delete($rq->file);
        return back()->with("success", "Usunięto");
    }

    /////////////// updaters ////////////////

    public function updateSettings(Request $rq)
    {
        foreach ($rq->except(["_token", "mode"]) as $name => $value) {
            Setting::find($name)->update(["value" => $value]);
        };

        return back()->with("success", "Ustawienia zostały zaktualizowane");
    }

    public function updateLogo(Request $rq)
    {
        foreach (["logo", "favicon"] as $icon_type) {
            if ($rq->file($icon_type) === null) {
                continue;
            }

            if ($rq->file($icon_type)->extension() !== "png") {
                return back()->with("error", "Logo musi mieć rozszerzenie .png");
            }

            $rq->file($icon_type)?->storeAs(
                "meta",
                $icon_type . "." . $rq->file($icon_type)->extension(),
                "public"
            );
        }

        return back()->with("success", "Logo zostało zaktualizowane");
    }

    public function updateWelcomeText(Request $rq)
    {
        Setting::find("welcome_text_content")->update(["value" => $rq->welcome_text_content]);
        Setting::find("welcome_text_visible")->update(["value" => $rq->welcome_text_visible]);
        return back()->with("success", "Tekst powitalny zaktualizowany");
    }

    public function updateTopNavPages(Request $rq)
    {
        $form_data = [
            "name" => $rq->name,
            "ordering" => $rq->ordering,
            "content" => $rq->content,
        ];

        if (Str::startsWith($rq->mode, "save")) {
            $page = (!$rq->id)
                ? TopNavPage::create($form_data)
                : TopNavPage::find($rq->id)->update($form_data);
            return redirect(route("top-nav-pages-edit", ["id" => ($rq->mode == "saveAndNew") ? null : $rq->id ?? $page->id]))
                ->with("success", "Strona została zapisana");
        } else if ($rq->mode == "delete") {
            TopNavPage::find($rq->id)->delete();
            return redirect(route("top-nav-pages"))->with("success", "Strona została usunięta");
        } else {
            abort(400, "Updater mode is missing or incorrect");
        }
    }

    public function updateCategories(Request $rq)
    {
        $form_data = $rq->except(["_token", "mode", "id"]);

        if (Str::startsWith($rq->mode, "save")) {
            $category = (!$rq->id)
                ? Category::create($form_data)
                : Category::find($rq->id)->update($form_data);
            return redirect(route("categories-edit", ["id" => ($rq->mode == "saveAndNew") ? null : $rq->id ?? $category->id]))
                ->with("success", "Kategoria została zapisana");
        } else if ($rq->mode == "delete") {
            Category::find($rq->id)->delete();
            Product::doesntHave("categories")->delete(); // delete products without categories
            return redirect(route("categories"))->with("success", "Kategoria została usunięta");
        } else {
            abort(400, "Updater mode is missing or incorrect");
        }
    }

    public function updateProducts(Request $rq)
    {
        $form_data = $rq->except(["_token", "mode"]);
        $categories = array_filter(explode(",", $form_data["categories"] ?? ""));

        $magazyn_product = Http::get(env("MAGAZYN_API_URL") . "products/" . $rq->id);
        if ($magazyn_product->notFound()) {
            return back()->with("error", "Produkt o podanym SKU nie istnieje w Magazynie");
        }

        $magazyn_product = $magazyn_product->json();
        $form_data = array_merge($form_data, $magazyn_product);

        if ($rq->mode == "save") {
            $product = Product::updateOrCreate(["id" => $rq->id], $form_data);
            $product->categories()->sync($categories);
            return redirect(route("products-edit", ["id" => $rq->id ?? $product->id]))->with("success", "Produkt został zapisany");
        } else if ($rq->mode == "delete") {
            Product::find($rq->id)->delete();
            return redirect(route("products"))->with("success", "Produkt został usunięty");
        } else {
            abort(400, "Updater mode is missing or incorrect");
        }
    }
}
