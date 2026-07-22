<?php

use App\Http\Controllers\FrontController;
use App\Http\Controllers\ProductController;
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

Route::controller(ProductController::class)->group(function () {
    Route::prefix("categories")->group(function () {
        Route::get("/for-front", "getCategoriesForFront");
        Route::get("/{id?}", "getCategory");
    });

    Route::prefix("products")->group(function () {
        Route::get("{id?}", "getProductData");
        Route::get("{id}/thumbnail", "getProductFamilyThumbnail");
    });
});

Route::controller(FrontController::class)->prefix("front")->group(function () {
    Route::get("category/{category?}", "tiles");
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
