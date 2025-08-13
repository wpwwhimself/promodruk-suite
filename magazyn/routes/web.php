<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Client\Request;
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

Route::redirect("/", "admin/dashboard");

Route::controller(AuthController::class)->prefix("auth")->group(function () {
    Route::get("/login", "input")->name("login");
    Route::post("/login", "authenticate")->name("authenticate");
    Route::post("/change-password", "changePassword")->name("change-password");
    Route::middleware("auth")->get("/logout", "logout")->name("logout");
});

Route::middleware("auth")->controller(AdminController::class)->prefix("admin")->group(function () {
    foreach(AdminController::$pages as [$label, $route, $role]) {
        $role
            ? Route::middleware("role:$role")->get(Str::slug($route), Str::camel($route))->name(Str::kebab($route))
            : Route::get(Str::slug($route), Str::camel($route))->name(Str::kebab($route));

        if ($route !== "dashboard") {
            Route::get($route."/edit/{id?}", Str::singular(Str::camel($route))."Edit")->name("$route-edit");
        }
    }

    Route::middleware("role:Edytor")->group(function () {
        Route::prefix("products")->group(function () {
            Route::get("edit-family/{id?}", "productFamilyEdit")->name("products-edit-family");
            Route::post("update-product-families", "updateProductFamilies")->name("update-product-families");

            Route::get("generate-variants/{family_id}", "productGenerateVariants")->name("product-generate-variants");
            Route::post("generate-variants-process", "productGenerateVariantsProcess")->name("product-generate-variants-process");

            Route::get("import-specs/{entity_name}/{id}", "productImportSpecs")->name("products-import-specs");
            Route::post("import-specs", "productImportSpecsProcess")->name("products-import-specs-process");

            Route::get("discount-exclusions", "productDiscountExclusions")->name("product-discount-exclusions");
            Route::get("discount-exclusions/toggle/{family_id}", "productDiscountExclusionsToggle")->name("product-discount-exclusions-toggle");
        });

        Route::prefix("main-attributes")->group(function () {
            Route::get("/edit/{id}", "mainAttributeEdit")->name("main-attributes-edit");
            Route::get("/prune", "mainAttributePrune")->name("main-attributes-prune");

            Route::prefix("primary-colors")->group(function () {
                Route::get("/list", "primaryColorsList")->name("primary-colors-list");
                Route::get("/edit/{id?}", "primaryColorEdit")->name("primary-color-edit");
                Route::post("/process", "primaryColorProcess")->name("primary-color-process");
            });
        });

        Route::prefix("attributes")->group(function () {
            Route::prefix("alt")->group(function () {
                Route::get("/edit/{attribute?}", "aatrEdit")->name("alt-attributes-edit");
                Route::post("/process", "aatrProcess")->name("alt-attributes-process");

                Route::get("/text-editor/test-tile", "aatrTestTextTile")->name("alt-attributes-text-editor-test-tile");
                Route::get("/text-editor", "aatrTextEditor")->name("alt-attributes-text-editor");
            });
        });

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

    Route::middleware("role:Administrator")->group(function () {
        Route::prefix("users")->group(function () {
            Route::get("/reset-password/{user_id}", "resetPassword")->name("users.reset-password");
        });
        Route::prefix("synchronizations")->group(function () {
            Route::get("/edit/{supplier_name}", "synchronizationEdit")->name("synchronizations-edit");
        });
    });
});

Route::prefix("test")->group(function () {
    Route::get("{handler}", function (string $handler) {
        $handler = ucfirst($handler);
        $className = "App\\DataIntegrators\\".$handler."Handler";
        (new $className(App\Models\ProductSynchronization::find($handler)))->test();
    });
});
