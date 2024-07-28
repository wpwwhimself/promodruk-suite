<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MainController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StockController;
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

Route::controller(MainController::class)->group(function () {
    Route::get("/", "index")->name("main");
    Route::post("/", "goToStock")->name("go-to-stock");
});

Route::controller(StockController::class)->prefix("stock")->group(function () {
    Route::get("/{product_code}", "stock")->name("stock");
});

Route::controller(AuthController::class)->prefix("auth")->group(function () {
    Route::get("/login", "input")->name("login");
    Route::post("/login", "authenticate")->name("authenticate");
    Route::middleware("auth")->get("/logout", "logout")->name("logout");
});

Route::middleware("auth")->controller(AdminController::class)->prefix("admin")->group(function () {
    Route::redirect("/", "admin/dashboard");

    foreach(AdminController::$pages as [$label, $route]) {
        Route::get(Str::slug($route), Str::camel($route))->name(Str::kebab($route));

        if ($route !== "dashboard") {
            Route::get($route."/edit/{id?}", Str::singular($route)."Edit")->name("$route-edit");
        }
    }

    Route::prefix("products/import")->group(function () {
        Route::get("/", "productImport")->name("products-import");
        Route::post("/", "productImportFetch")->name("products-import-fetch");
        Route::post("/choose", "productImportChoose")->name("products-import-choose");
    });

    Route::prefix("settings/update")->group(function () {
        foreach(AdminController::$updaters as $slug) {
            Route::post(Str::slug($slug), Str::camel("update-".$slug))->name(Str::kebab("update-".$slug));
        }
    });
});

