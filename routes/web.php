<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Models\TopNavPage;
use Illuminate\Support\Facades\Route;

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

Route::get('/', function () {
    return view('main');
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

Route::controller(AuthController::class)->prefix("auth")->group(function () {
    Route::get("/login", "input")->name("login");
    Route::post("/login", "authenticate")->name("authenticate");
    Route::middleware("auth")->get("/logout", "logout")->name("logout");
});

Route::middleware("auth")->controller(AdminController::class)->prefix("admin")->group(function () {
    Route::get("/", "index")->name("dashboard");
});
