<?php

use App\Models\Category;
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
        Category::whereNull("slug")->each(function (Category $c) {
            $data = [
                "parent_id" => $c->parent_id,
                "name" => $c->name,
            ];
            $data = Category::autofillOnSave($data);
            $c->update($data);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
