<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EnMasseController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShoppingCartController;
use App\Http\Controllers\SpellbookController;
use App\Http\Controllers\SupervisorController;
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
    Route::get("kategoria/{id}", fn (int $id) => App::make(ProductController::class)->listCategory(Category::findOrFail($id)));

    Route::get("szukaj/{query?}", "listSearchResults")->name("search-results");
    Route::post("szukaj", fn(Request $rq) => redirect()->route("search-results", ["query" => $rq->input("query")]))->name("search");
    Route::get("{id?}", "listProduct")->name("product")->where("id", ".*");
});

Route::controller(FileController::class)->prefix("pliki")->group(function () {
    Route::get("pobierz", "download")->name("file-download");
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
    Route::middleware("auth")->group(function () {
        Route::get("/logout", "logout")->name("logout");
        Route::get("/change-password", "changePassword")->name("change-password");
        Route::post("/change-password", "processChangePassword")->name("process-change-password");
    });
});

Route::middleware("auth")->group(function () {
    Route::controller(AdminController::class)->prefix("admin")->group(function () {
        Route::redirect("/", "admin/dashboard");

        foreach(AdminController::$pages as [$label, $route]) {
            Route::get(Str::slug($route), Str::camel($route))->name(Str::kebab($route));

            if ($route !== "dashboard" && $route !== "settings") {
                Route::get($route."/edit/{id?}", Str::singular(Str::camel($route))."Edit")->name("$route-edit");
            }
        }

        Route::prefix("users")->group(function () {
            Route::get("/reset-password/{user_id}", "resetPassword")->name("users.reset-password");
        });

        Route::prefix("products/import")->group(function () {
            Route::get("init", "productImportInit")->name("products-import-init");
            Route::post("fetch", "productImportFetch")->name("products-import-fetch");
            Route::post("import", "productImportImport")->name("products-import-import");

            Route::get("refresh/status", "productImportRefreshStatus")->name("products-import-refresh-status");        });
            Route::get("refresh", "productImportRefresh")->name("products-import-refresh");

        Route::prefix("settings/update")->group(function () {
            foreach(AdminController::$updaters as $slug) {
                Route::post(Str::slug($slug), Str::camel("update-".$slug))->name(Str::kebab("update-".$slug));
            }
        });

        Route::prefix("files")->group(function () {
            Route::get("download", "filesDownload")->name("files-download");
            Route::post("upload", "filesUpload")->name("files-upload");
            Route::get("delete", "filesDelete")->name("files-delete");

            Route::get("search", "filesSearch")->name("files-search");

            Route::prefix("folder")->group(function () {
                Route::get("new", "folderNew")->name("folder-new");
                Route::post("create", "folderCreate")->name("folder-create");
                Route::get("delete", "folderDelete")->name("folder-delete");
            });
        });
    });

    Route::controller(SupervisorController::class)->prefix("admin/supervisors")->group(function () {
        Route::get("edit/{id?}", "edit")->name("supervisor-edit");
        Route::post("submit", "submit")->name("supervisor-submit");
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

Route::controller(SpellbookController::class)->group(function () {
    foreach (SpellbookController::SPELLS as $spell_name => $route) {
        Route::get($route, $spell_name);
    }
});
