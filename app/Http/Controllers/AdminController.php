<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
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
    ];

    public static $updaters = [
        "settings",
        "logo",
        "welcome-text",
        "top-nav-pages",
        "categories",
        "products",
    ];

    /////////////// pages ////////////////

    public function dashboard()
    {
        $general_settings = Setting::where("group", "general")->get();
        [$welcome_text_content, $welcome_text_visible] = Setting::where("group", "welcome_text")->get();

        return view("admin.dashboard", compact(
            "general_settings",
            "welcome_text_content",
            "welcome_text_visible",
        ));
    }

    public function topNavPages()
    {
        $perPage = request("perPage", 100);
        $sortBy = request("sortBy", "name");

        $pages = TopNavPage::all();

        if (Str::startsWith($sortBy, "-")) $pages = $pages->sortByDesc(Str::afterLast($sortBy, "-"));
        else $pages = $pages->sortBy($sortBy);

        $pages = new LengthAwarePaginator(
            $pages->slice($perPage * (request("page") - 1), $perPage),
            $pages->count(),
            $perPage,
            request("page"),
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
        $sortBy = request("sortBy", "name");

        $categories = Category::all();

        if (Str::startsWith($sortBy, "-")) $categories = $categories->sortByDesc(Str::afterLast($sortBy, "-"));
        else $categories = $categories->sortBy($sortBy);

        $categories = new LengthAwarePaginator(
            $categories->slice($perPage * (request("page") - 1), $perPage),
            $categories->count(),
            $perPage,
            request("page"),
            ["path" => ""]
        );

        return view("admin.categories", compact(
            "categories",
            "perPage",
            "sortBy",
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

        $products = Product::all();

        if (Str::startsWith($sortBy, "-")) $products = $products->sortByDesc(Str::afterLast($sortBy, "-"));
        else $products = $products->sortBy($sortBy);

        $products = new LengthAwarePaginator(
            $products->slice($perPage * (request("page") - 1), $perPage),
            $products->count(),
            $perPage,
            request("page"),
            ["path" => ""]
        );
        return view("admin.products", compact(
            "products",
            "perPage",
            "sortBy",
        ));
    }
    public function productEdit(string $id = null)
    {
        $product = ($id) ? Product::findOrFail($id) : null;
        return view("admin.product", compact(
            "product",
        ));
    }

    public function productImportInit(string $supplier = null, string $category = null)
    {
        $data = ($category) ? Http::get(env("MAGAZYN_API_URL") . "products/by/$supplier/$category")->collect()
            : ($supplier ? Http::get(env("MAGAZYN_API_URL") . "products/by/$supplier")->collect()
                ->mapWithKeys(fn ($p) => [$p["original_category"] => $p["original_category"]])
                ->sort()
            : Http::get(env("MAGAZYN_API_URL") . "suppliers")->collect()
                ->mapWithKeys(fn ($s) => ["$s[name] ($s[prefix])" => $s["prefix"]])
                ->sort()
        );
        return view("admin.product-import", compact("data", "supplier", "category"));
    }
    public function productImportFetch(Request $rq)
    {
        return redirect()->route('products-import-init', ['supplier' => $rq->supplier, 'category' => $rq->category]);
    }
    public function productImportImport(Request $rq)
    {
        $products = Http::get(env("MAGAZYN_API_URL") . "products/by/$rq->supplier/$rq->category")->collect()
            ->filter(fn ($p) => in_array($p["id"], $rq->ids));
        $categories = array_filter(explode(",", $rq->categories ?? ""));

        foreach ($products as $product) {
            $product = Product::updateOrCreate(["id" => $product["id"]], [
                "product_family_id" => $product["product_family_id"],
                "visible" => true,
                "name" => $product["name"],
                "description" => $product["description"],
                "images" => $product["images"],
                "thumbnails" => $product["thumbnails"],
                "color" => $product["color"],
                "attributes" => $product["attributes"],
            ]);

            $product->categories()->sync($categories);
        }

        return redirect()->route("products")->with("success", "Produkty zostały zaimportowane");
    }

    public function productImportRefresh()
    {
        $products = Http::post(env("MAGAZYN_API_URL") . "products/for-refresh", [
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
            ]);
        }

        return redirect()->route("products")->with("success", "Produkty zostały odświeżone");
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
        if ($rq->file("logo")->extension() !== "png") {
            return back()->with("error", "Logo musi mieć rozszerzenie .png");
        }

        if (!$rq->file("logo")->storeAs(
            "meta",
            "logo.".$rq->file("logo")->extension(),
            "public"
        )) {
            return back()->with("error", "Logo nie zostało zaktualizowane");
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
        foreach(["visible"] as $label) { // checkboxes
            $form_data[$label] = $rq->has($label);
        }

        if (Str::startsWith($rq->mode, "save")) {
            $category = (!$rq->id)
                ? Category::create($form_data)
                : Category::find($rq->id)->update($form_data);
            return redirect(route("categories-edit", ["id" => ($rq->mode == "saveAndNew") ? null : $rq->id ?? $category->id]))
                ->with("success", "Kategoria została zapisana");
        } else if ($rq->mode == "delete") {
            Category::find($rq->id)->delete();
            return redirect(route("categories"))->with("success", "Kategoria została usunięta");
        } else {
            abort(400, "Updater mode is missing or incorrect");
        }
    }

    public function updateProducts(Request $rq)
    {
        $form_data = $rq->except(["_token", "mode"]);
        foreach(["visible"] as $label) { // checkboxes
            $form_data[$label] = $rq->has($label);
        }
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
