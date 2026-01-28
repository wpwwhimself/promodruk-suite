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
        foreach ([
            [
                "name" => "Oferty",
                "visible" => 1,
                "order" => 1,
                "icon" => model_icon("offers"),
                "target_type" => 1,
                "target_name" => "admin.model.list",
                "target_params" => ["model" => "offers"],
                "roles" => ["offer-manager"],
            ],
            [
                "name" => "Dostawcy",
                "visible" => 1,
                "order" => 2,
                "icon" => model_icon("suppliers"),
                "target_type" => 1,
                "target_name" => "admin.model.list",
                "target_params" => ["model" => "suppliers"],
                "roles" => ["technical"],
            ],
            [
                "name" => "Konta",
                "visible" => 1,
                "order" => 3,
                "icon" => model_icon("users"),
                "target_type" => 1,
                "target_name" => "admin.model.list",
                "target_params" => ["model" => "users"],
                "roles" => ["technical"],
            ],
        ] as $data) {
            $item = NavItem::create($data);
            $item->roles()->sync($data["roles"]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        NavItem::delete();
    }
};
