<?php

namespace App\Http\Controllers;

use App\Jobs\RefreshProductsJob;
use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\ProductTag;
use App\Models\Setting;
use App\Models\Supervisor;
use App\Models\TopNavPage;
use App\Models\User;
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
    public static $pages = [
        ["Kokpit", "dashboard", null],
        ["Ustawienia", "settings", "Administrator"],
        ["Konta", "users", "Administrator"],
        ["Strony", "top-nav-pages", "Edytor"],
        ["Kategorie", "categories", "Edytor"],
        ["Produkty", "products", "Edytor"],
        ["Tagi produktów", "product-tags", "Edytor"],
        ["Pliki", "files", "Edytor"],
    ];

    private static function checkRole(string $page_name)
    {
        $page = collect(self::$pages)->firstWhere("1", $page_name);
        if ($page === null) abort(403);
        if (!userIs($page[2])) abort(403);
    }

    public static $updaters = [
        "settings",
        "logo",
        "welcome-text",
        "users",
        "top-nav-pages",
        "categories",
        "products",
        "product-tags",
        "files",
    ];

    #region pages
    public function dashboard()
    {
        return view("admin.dashboard");
    }

    public function settings()
    {
        self::checkRole("settings");

        $general_settings = Setting::where("group", "general")->get();
        [$welcome_text_content, $welcome_text_visible] = Setting::where("group", "welcome_text")->get();
        $queries_settings = Setting::where("group", "queries")->get();
        $showcase_settings = Setting::where("group", "showcase")->get();
        $auxiliary_products_visibility_settings = Setting::where("group", "auxiliary_products_visibility")->get();

        $supervisors = Supervisor::all();

        return view("admin.settings", compact(
            "general_settings",
            "welcome_text_content",
            "welcome_text_visible",
            "queries_settings",
            "showcase_settings",
            "auxiliary_products_visibility_settings",
            "supervisors",
        ));
    }

    public function users()
    {
        self::checkRole("users");

        $users = User::orderBy("name")->get();

        return view("admin.users.list", compact(
            "users",
        ));
    }
    public function userEdit(?int $id = null)
    {
        if (!userIs("Administrator") && Auth::id() != $id) abort(403);

        $user = $id
            ? User::find($id)
            : null;
        $roles = Role::all();

        // nobody can edit super but super
        if ($user?->name == "super" && Auth::id() != $user?->id) abort(403);

        return view("admin.users.edit", compact(
            "user",
            "roles",
        ));
    }

    public function topNavPages()
    {
        self::checkRole("top-nav-pages");

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
    public function topNavPageEdit(?int $id = null)
    {
        $page = ($id) ? TopNavPage::findOrFail($id) : null;

        return view("admin.top-nav-page", compact(
            "page"
        ));
    }

    public function categories()
    {
        self::checkRole("categories");

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

        $catsForFiltering = Category::whereNull("parent_id")
            ->get()
            ->flatMap(fn ($cat) => $cat->all_children)
            ->mapWithKeys(fn ($cat) => [(str_repeat("- ", $cat->depth) . $cat->name) => $cat->id]);

        return view("admin.categories", compact(
            "categories",
            "perPage",
            "sortBy",
            "catsForFiltering",
        ));
    }
    public function categoryEdit(?int $id = null)
    {
        self::checkRole("categories");

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
        self::checkRole("products");

        $perPage = request("perPage", 100);
        $sortBy = request("sortBy", "name");

        $products = Product::all()
            ->filter(fn ($p) => (request("query"))
                ? Str::of($p->name)->contains(request("query"), true)
                    || Str::of($p->description)->contains(request("query"), true)
                    || Str::of($p->front_id)->contains(request("query"), true)
                : true
            )
            ->sort(fn ($a, $b) => $a[$sortBy] <=> $b[$sortBy])
            ->filter(fn ($prod) => (isset(request("filters")["cat_id"]))
                ? in_array(request("filters")["cat_id"], $prod->categories->pluck("id")->toArray())
                : true
            )
            ->filter(fn ($prod) => (isset(request("filters")["visibility"]))
                ? $prod->visible == request("filters")["visibility"]
                : true
            )
            ->groupBy("product_family_id");

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
                if ($cat->products->groupBy("product_family_id")->count() > 0) $name .= " (" . $cat->products->groupBy("product_family_id")->count() . ")";
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
    public function productEdit(?string $id = null)
    {
        self::checkRole("products");

        $family = ($id) ? Product::familyByPrefixedId($id)->get() : null;
        $product = $family?->first();
        $tags = ProductTag::ordered()->get();

        return view("admin.product", compact(
            "family",
            "product",
            "tags",
        ));
    }

    public function productImportInit()
    {
        self::checkRole("products");

        $data = Http::get(env("MAGAZYN_API_URL") . "suppliers")->collect()
            ->pluck("source", "name")
            ->sortKeys();

        return view("admin.product-import", compact("data"));
    }
    public function productImportFetch(Request $rq)
    {
        self::checkRole("products");

        [$source, $category, $query] = [$rq->source, $rq->category, $rq->get("query")];

        $data = ($category || $query)
            ? Http::get(env("MAGAZYN_API_URL") . "products/by/$source/".($category ?? '---')."/$query")->collect()
            : Http::get(env("MAGAZYN_API_URL") . "products/by/$source")->collect()
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
                    "description" => $product["combined_description"] ?? null,
                    "description_label" => $product["product_family"]["description_label"],
                    "images" => $product["combined_images"] ?? null,
                    "thumbnails" => $product["combined_thumbnails"] ?? null,
                    "color" => $product["color"],
                    "sizes" => $product["sizes"],
                    "extra_filtrables" => $product["extra_filtrables"],
                    "brand_logo" => $product["brand_logo"],
                    "original_sku" => $product["original_sku"],
                    "price" => $product["show_price"] ? $product["price"] : null,
                    "tabs" => $product["combined_tabs"] ?? null,
                ]);

                $created_product->categories()->sync($categories);
            }
        }

        return redirect()->route("products")->with("success", "Produkty zostały zaimportowane");
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

        return redirect()->route("products")->with("success", "Wymuszono odświeżenie");
    }

    public function productImportRefreshStatus(): View
    {
        $refreshData = json_decode(
            Setting::find("product_refresh_status")->value,
            true
        );

        return view("components.product-refresh-status", compact(
            "refreshData"
        ));
    }
    #endregion

    #region helpers product tags
    public function productTags(Request $rq)
    {
        self::checkRole("product-tags");

        $tags = ProductTag::ordered()->get();
        $product = Product::all()->random(1)->first();
        return view("admin.product-tags.list", compact("tags", "product"));
    }

    public function productTagEdit($id = null)
    {
        self::checkRole("product-tags");

        $tag = ($id) ? ProductTag::find($id) : null;
        $product = Product::all()->random(1)->first();
        return view("admin.product-tags.edit", compact("tag", "product"));
    }

    public function productTagEnable(Request $rq)
    {
        Product::where("product_family_id", $rq->product_family_id)
            ->first()
            ->tags()
            ->updateExistingPivot($rq->tag_id, ["disabled" => !$rq->enable]);

        return back()->with("success", "Zapisano");
    }
    #endregion

    #region files
    public function files()
    {
        self::checkRole("files");

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

    public function updateUsers(Request $rq)
    {
        $form_data = $rq->except(["_token", "roles"]);
        if (!$rq->id) {
            $form_data["password"] = $rq->name;
        }

        $user = User::updateOrCreate(
            ["id" => $rq->id],
            $form_data
        );
        $user->roles()->sync($rq->roles);

        return redirect()->route("users")->with("success", "Dane użytkownika zmienione");
    }

    public function updateTopNavPages(Request $rq)
    {
        $form_data = [
            "name" => $rq->name,
            "ordering" => $rq->ordering,
            "content" => $rq->content,
            "show_in_top_nav" => $rq->has("show_in_top_nav"),
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
        $form_data["banners"] = json_decode($form_data["banners"], true);

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
        $form_data = $rq->except(["_token", "mode", "id"]);
        $categories = array_filter($form_data["categories"] ?? []);
        foreach ([
            "hide_family_sku_on_listing",
        ] as $boolean) {
            $form_data[$boolean] = $rq->has($boolean);
        }

        $magazyn_data = Http::get(env("MAGAZYN_API_URL") . "products/$rq->id/1");
        if ($magazyn_data->notFound()) {
            return back()->with("error", "Produkt o podanym SKU nie istnieje w Magazynie");
        }
        $magazyn_data = $magazyn_data->json();

        if ($rq->mode == "save") {
            $family = Product::where("product_family_id", $rq->id)->get();

            foreach ($magazyn_data as $magazyn_product) {
                foreach (["images", "thumbnails", "description", "tabs"] as $key) {
                    $magazyn_product[$key] = $magazyn_product["combined_$key"];
                }
                $form_data["description_label"] = $magazyn_product["product_family"]["description_label"];
                $form_data["subtitle"] = $magazyn_product["product_family"]["subtitle"];
                $form_data["family_name"] = $magazyn_product["product_family"]["name"];
                $form_data["price"] = $magazyn_product["show_price"] ? $magazyn_product["price"] : null;

                $ofertownik_product = Product::updateOrCreate(["id" => $magazyn_product["id"]], array_merge($magazyn_product, $form_data));
                $ofertownik_product->categories()->sync($categories);
            }

            foreach ($family->whereNotIn("id", array_map(fn ($p) => $p["id"], $magazyn_data)) as $ofertownik_product) {
                $ofertownik_product->delete();
            }

            // tags
            if ($form_data["new_tag"]["id"]) {
                $family->first()->tags()->attach(
                    $form_data["new_tag"]["id"],
                    [
                        "start_date" => $form_data["new_tag"]["start_date"],
                        "end_date" => $form_data["new_tag"]["end_date"],
                        "disabled" => false,
                    ]
                );
            }

            return redirect(route("products-edit", ["id" => $magazyn_data[0]["product_family"]["prefixed_id"]]))->with("success", "Produkt został zapisany");
        } else if ($rq->mode == "delete") {
            Product::where("product_family_id", $rq->id)->delete();
            return redirect(route("products"))->with("success", "Produkt został usunięty");
        } else if (Str::startsWith($rq->mode, "delete_tag")) {
            $product = Product::where("product_family_id", $rq->id)->first();
            $product->tags()->detach($rq->tag_id);
            return redirect(route("products-edit", ["id" => $product->family_prefixed_id]))->with("success", "Tag został usunięty");
        } else {
            abort(400, "Updater mode is missing or incorrect");
        }
    }

    public function updateProductTags(Request $rq)
    {
        $form_data = $rq->except(["_token", "mode", "id"]);
        $form_data["gives_priority_on_listing"] = $rq->has("gives_priority_on_listing");

        if ($rq->mode == "save") {
            $tag = ProductTag::updateOrCreate(["id" => $rq->id], $form_data);
            return redirect(route("product-tags-edit", ["id" => $tag->id]))->with("success", "Tag został zapisany");
        } else if ($rq->mode == "delete") {
            $tag = ProductTag::find($rq->id);
            $tag->products->groupBy("product_family_id")->each(fn ($pf) => $pf->first()->tags()->detach($tag->id));
            $tag->delete();
            return redirect(route("product-tags"))->with("success", "Tag został usunięty");
        } else {
            abort(400, "Updater mode is missing or incorrect");
        }
    }
    #endregion

    #region helpers
    public function resetPassword(int $user_id)
    {
        $user = User::find($user_id);
        $user->update(["password" => $user->name]);

        return back()->with("success", "Hasło użytkownika zresetowane");
    }
    #endregion
}
