<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\EnMasseController;
use App\Http\Controllers\FrontController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShoppingCartController;
use App\Http\Controllers\TopNavController;
use App\Http\Middleware\Shipyard\EnsureUserHasRole;
use Illuminate\Http\Request;
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

if (file_exists(__DIR__.'/Shipyard/shipyard.php')) require __DIR__.'/Shipyard/shipyard.php';

Route::redirect("/pages/{slug}", "/{slug}")->where("slug", ".*");

Route::controller(ProductController::class)->group(function () {
    Route::get('/', "home")->name("home");

    Route::prefix("kategorie")->group(function () {
        Route::get("{slug}", "home")->name("category")->where("slug", ".*");
    });

    Route::prefix("produkty")->group(function () {
        Route::get("szukaj", "listSearchResults")->name("search-results");
        Route::post("szukaj", fn(Request $rq) => redirect()->route("search-results", ["query" => $rq->input("query")]))->name("search");
        Route::get("{id?}", "listProduct")->name("product")->where("id", ".*");
    });
});

Route::controller(ShoppingCartController::class)->prefix("koszyk")->group(function () {
    Route::get("/", "index")->name("cart");
    Route::post("add", "add")->name("add-to-cart");
    Route::post("mod", "mod")->name("mod-cart");
    Route::get("zapytanie", "prepareQuery")->name("prepare-query");
    Route::post("send", "sendQuery")->name("send-query");
});

Route::controller(TopNavController::class)->group(function () {
    Route::get("{slug}", "show")->name("top-nav.show");
});

Route::middleware("auth")->group(function () {
    Route::controller(AdminController::class)->prefix("admin")->group(function () {

        Route::prefix("categories")->group(function () {
            Route::post("update-ordering", "categoryUpdateOrdering")->name("categories-update-ordering");
        });

        Route::prefix("products")->middleware(EnsureUserHasRole::class.":product-manager")->group(function () {
            Route::get("", "products")->name("products");
            Route::get("edit/{id?}", "productEdit")->name("products-edit");
            Route::post("", "updateProducts")->name("update-products");

            Route::prefix("import")->group(function () {
                Route::get("init", "productImportInit")->name("products-import-init");
                Route::post("fetch", "productImportFetch")->name("products-import-fetch");
                Route::post("import", "productImportImport")->name("products-import-import");

                Route::get("refresh/status", "productImportRefreshStatus")->name("products-import-refresh-status");
                Route::get("refresh", "productImportRefresh")->name("products-import-refresh");

                Route::get("unsynced/list", "productUnsyncedList")->name("products-unsynced-list");
                Route::post("unsynced/delete", "productUnsyncedDelete")->name("products-unsynced-delete");
            });

            Route::prefix("ordering")->group(function () {
                Route::get("manage/{category?}", "productOrderingManage")->name("products-ordering-manage");
                Route::post("manage", "productOrderingSubmit")->name("products-ordering-submit");
            });

            Route::prefix("category-assignment")->group(function () {
                Route::get("manage/{category?}", "productCategoryAssignmentManage")->name("products-category-assignment-manage");
                Route::post("manage", "productCategoryAssignmentSubmit")->name("products-category-assignment-submit");
            });
        });

        Route::prefix("product-tags")->group(function () {
            Route::post("update-for-products", "productTagUpdateForProducts")->name("product-tag-update-for-products");
        });
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
