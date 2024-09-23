<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\UserController;
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
        Route::get("/edit/{id?}", "edit")->name("users.edit");
        Route::post("/edit", "process")->name("users.process");
    });

    Route::controller(OfferController::class)->prefix("offers")->middleware("role:offer-maker")->group(function () {
        Route::get("/", "offer")->name("offers.offer");
        Route::post("/prepare", "prepare")->name("offers.prepare");
    });
});
