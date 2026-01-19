<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class FrontController extends Controller
{
    public function tiles(?Category $category = null, Request $rq)
    {
        return response()->json([
            "data" => $category,
            "tiles" => view("components.browser.category-tiles", [
                "category" => $category,
            ])->render(),
            "sidebar" => view("components.browser.category-tree", [
                "category" => $category,
            ])->render(),
        ]);
    }
}
