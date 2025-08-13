<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\DocumentOutputController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
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

Route::redirect("/", "/dashboard");

Route::controller(AuthController::class)->prefix("auth")->group(function () {
    Route::get("/login", "input")->name("login");
    Route::post("/login", "authenticate")->name("authenticate");
    Route::post("/change-password", "changePassword")->name("change-password");
    Route::middleware("auth")->get("/logout", "logout")->name("logout");
});

Route::middleware("auth")->group(function () {
    Route::get("/dashboard", function () {
        return view("pages.dashboard");
    })->name("dashboard");

    Route::controller(UserController::class)->prefix("users")->middleware("role:technical")->group(function () {
        Route::get("/", "list")->name("users.list");
        Route::withoutMiddleware("role:technical")->get("/edit/{id?}", "edit")->name("users.edit");
        Route::withoutMiddleware("role:technical")->get("me", fn () => redirect()->route("users.edit", ["id" => Auth::id()]))->name("users.me");
        Route::post("/edit", "process")->name("users.process");
        Route::get("/reset-password/{user_id}", "resetPassword")->name("users.reset-password");
    });

    Route::controller(OfferController::class)->prefix("offers")->middleware("role:offer-maker")->group(function () {
        Route::get("/", "list")->name("offers.list");
        Route::get("/show/{id?}", "offer")->name("offers.offer");
        Route::post("/save", "save")->name("offers.save");
    });

    Route::controller(SupplierController::class)->prefix("suppliers")->middleware("role:technical")->group(function () {
        Route::get("/", "list")->name("suppliers.list");
        Route::get("/edit/{id?}", "edit")->name("suppliers.edit");
        Route::post("/edi", "process")->name("suppliers.process");
    });

    Route::controller(DocumentationController::class)->prefix("docs")->group(function () {
        Route::get("/{slug}", "show")->name("docs.show");
    });
});

Route::controller(DocumentOutputController::class)->prefix("documents")->group(function () {
    Route::prefix("offer")->middleware("role:offer-maker")->group(function () {
        Route::get("processed", "processedOffers")->name("documents.offers");
        Route::get("processed/delete/{file?}", "processedOffersDelete")->name("documents.offers.delete");

        Route::get("{format}/{id}", "processOffer")->name("documents.offer");
    });
});
