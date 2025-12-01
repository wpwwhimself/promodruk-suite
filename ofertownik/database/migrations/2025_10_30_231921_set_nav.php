<?php

use App\Models\Shipyard\NavItem;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $navItem = NavItem::create([
            "name" => "Konta",
            "visible" => 1,
            "order" => 1,
            "icon" => model_icon("users"),
            "target_type" => 1,
            "target_name" => "admin.model.list",
            "target_params" => ["model" => "users"],
        ]);
        $navItem->roles()->attach(["technical"]);

        $navItem = NavItem::create([
            "name" => "Kategorie",
            "visible" => 1,
            "order" => 2,
            "icon" => model_icon("categories"),
            "target_type" => 1,
            "target_name" => "admin.model.list",
            "target_params" => ["model" => "categories"],
        ]);
        $navItem->roles()->attach(["product-manager"]);

        $navItem = NavItem::create([
            "name" => "Produkty",
            "visible" => 1,
            "order" => 3,
            "icon" => model_icon("products"),
            "target_type" => 1,
            "target_name" => "products",
        ]);
        $navItem->roles()->attach(["product-manager"]);

        $navItem = NavItem::create([
            "name" => "Tagi produktów",
            "visible" => 1,
            "order" => 4,
            "icon" => model_icon("product-tags"),
            "target_type" => 1,
            "target_name" => "product-tags",
        ]);
        $navItem->roles()->attach(["product-manager"]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
