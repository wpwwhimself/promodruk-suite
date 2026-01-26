<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\DocumentOutputController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\Shipyard\EnsureUserHasRole;
use App\Models\Supplier;
use Illuminate\Support\Facades\Auth;
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

Route::redirect("/", "/profile");

Route::middleware("auth")->group(function () {
    Route::controller(OfferController::class)->prefix("offers")->middleware(EnsureUserHasRole::class.":offer-manager")->group(function () {
        Route::get("/", "list")->name("offers.list");
        Route::get("/show/{id?}", "offer")->name("offers.offer");
        Route::post("/save", "save")->name("offers.save");
    });

    Route::controller(SupplierController::class)->prefix("suppliers")->middleware(EnsureUserHasRole::class.":technical")->group(function () {
        Route::get("/", "list")->name("suppliers.list");
        Route::get("/edit/{id?}", "edit")->name("suppliers.edit");
        Route::post("/edi", "process")->name("suppliers.process");
    });
});

Route::controller(DocumentOutputController::class)->prefix("documents")->group(function () {
    Route::prefix("offer")->middleware(EnsureUserHasRole::class.":offer-maker")->group(function () {
        Route::get("processed", "processedOffers")->name("documents.offers");
        Route::get("processed/delete/{file?}", "processedOffersDelete")->name("documents.offers.delete");

        Route::get("{format}/{id}", "processOffer")->name("documents.offer");
    });
});
