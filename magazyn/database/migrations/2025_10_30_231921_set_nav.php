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
            "name" => "Produkty",
            "visible" => 1,
            "order" => 2,
            "icon" => model_icon("products"),
            "target_type" => 1,
            "target_name" => "products",
        ]);
        $navItem->roles()->attach(["product-manager"]);

        $navItem = NavItem::create([
            "name" => "Cechy",
            "visible" => 1,
            "order" => 3,
            "icon" => model_icon("main-attributes"),
            "target_type" => 1,
            "target_name" => "attributes",
        ]);
        $navItem->roles()->attach(["product-manager"]);

        $navItem = NavItem::create([
            "name" => "Dostawcy",
            "visible" => 1,
            "order" => 4,
            "icon" => model_icon("custom-suppliers"),
            "target_type" => 1,
            "target_name" => "suppliers",
        ]);
        $navItem->roles()->attach(["product-manager"]);

        $navItem = NavItem::create([
            "name" => "Synchronizacje",
            "visible" => 1,
            "order" => 5,
            "icon" => model_icon("product-synchronizations"),
            "target_type" => 1,
            "target_name" => "synchronizations",
        ]);
        $navItem->roles()->attach(["technical"]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
