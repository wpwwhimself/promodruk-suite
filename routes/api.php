<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StockController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::controller(StockController::class)->group(function () {
    Route::prefix("stock")->group(function () {
        Route::get("/strict/{product_code?}", "stockJsonStrict")->where("product_code", ".*");
        Route::get("/{product_code?}", "stockJson")->where("product_code", ".*");
    });
});

Route::controller(ProductController::class)->group(function () {
    Route::prefix("attributes")->group(function () {
        Route::get("/{id?}", "getAttributes");
    });
    Route::prefix("main-attributes")->group(function () {
        Route::get("/tile/{color_name}", "getColorTile");
        Route::get("/{id?}", "getMainAttributes");
    });
    Route::prefix("primary-colors")->group(function () {
        Route::get("/tile/{color_name}", "getPrimaryColorTile");
        Route::get("/{id?}", "getPrimaryColors");
    });
    Route::prefix("products")->group(function () {
        Route::post("by/ids", "getProductsByIds");
        Route::get("by/{supplier}/{category?}/{query?}", "getProductsForImport");
        Route::post("for-refresh", "getProductsForRefresh");
        Route::post("colors", "getProductColors");
        Route::get("for-markings", "getProductsForMarkings");
        Route::get("/{id?}/{soft?}", "getProducts")->where("id", "[0-9A-Z\-\.]+");
    });
    Route::prefix("suppliers")->group(function () {
        Route::get("/", "getSuppliers");
    });
});

Route::controller(AdminController::class)->group(function () {
    Route::prefix("synchronizations")->group(function () {
        Route::get("/", "getSynchData");
        Route::post("{action}", "synchMod")->name("synch-mod");
    });

    Route::prefix("products")->group(function () {
        Route::post("prepare-tabs", "prepareProductTabs");
        Route::get("get-original-categories/{supplier?}", "getOriginalCategories");
    });
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
