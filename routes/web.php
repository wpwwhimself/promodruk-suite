<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EnMasseController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShoppingCartController;
use App\Models\Category;
use App\Models\TopNavPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::controller(ProductController::class)->group(function () {
    Route::get('/', "home")->name("home");
});

// top nav routes
try {
    foreach (TopNavPage::ordered()->get() as $page) {
        Route::get(
            "/".$page->slug,
            fn () => view("top-nav-page", ["page" => $page])
        )->name($page->slug);
    }
} catch (Exception $e) {

}

Route::controller(ProductController::class)->prefix("produkty")->group(function () {
    foreach (Category::all() as $category) {
        Route::get(
            $category->tree->map(fn($cat) => Str::slug($cat->name))->join("/"),
            fn() => App::make(ProductController::class)->listCategory($category)
        )
            ->name("category-".$category->id);
    }
    Route::get("kategoria/{id}", fn(int $id) => redirect(route("category-$id")));

    Route::get("{id?}", "listProduct")->name("product");
    Route::get("szukaj/{query?}", "listSearchResults")->name("search-results");
    Route::post("szukaj", fn(Request $rq) => redirect()->route("search-results", ["query" => $rq->input("query")]))->name("search");
});

Route::controller(ShoppingCartController::class)->prefix("koszyk")->group(function () {
    Route::get("/", "index")->name("cart");
    Route::post("add", "add")->name("add-to-cart");
    Route::post("mod", "mod")->name("mod-cart");
    Route::get("zapytanie", "prepareQuery")->name("prepare-query");
    Route::post("send", "sendQuery")->name("send-query");
});

Route::controller(AuthController::class)->prefix("auth")->group(function () {
    Route::get("/login", "input")->name("login");
    Route::post("/login", "authenticate")->name("authenticate");
    Route::middleware("auth")->get("/logout", "logout")->name("logout");
});

Route::middleware("auth")->group(function () {
    Route::controller(AdminController::class)->prefix("admin")->group(function () {
        Route::redirect("/", "admin/dashboard");

        foreach(AdminController::$pages as [$label, $route]) {
            Route::get(Str::slug($route), Str::camel($route))->name(Str::kebab($route));

            if ($route !== "dashboard") {
                Route::get($route."/edit/{id?}", Str::singular(Str::camel($route))."Edit")->name("$route-edit");
            }
        }

        Route::prefix("products/import")->group(function () {
            Route::get("init", "productImportInit")->name("products-import-init");
            Route::post("fetch", "productImportFetch")->name("products-import-fetch");
            Route::post("import", "productImportImport")->name("products-import-import");

            Route::get("refresh", "productImportRefresh")->name("products-import-refresh");
        });

        Route::prefix("settings/update")->group(function () {
            foreach(AdminController::$updaters as $slug) {
                Route::post(Str::slug($slug), Str::camel("update-".$slug))->name(Str::kebab("update-".$slug));
            }
        });

        Route::prefix("files")->group(function () {
            Route::post("upload", "filesUpload")->name("files-upload");
        });
    });

    Route::controller(EnMasseController::class)->prefix("en-masse")->group(function () {
        foreach ([
            "init",
            "execute",
        ] as $fn) {
            Route::post(Str::slug($fn), Str::camel($fn))->name("en-masse-".Str::slug($fn));
        }
    });
});
