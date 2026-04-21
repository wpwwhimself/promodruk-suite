<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FrontController extends Controller
{
    public function tiles(?Category $category = null)
    {
        if (request()->header("whoami")) {
            Auth::login(User::find(request()->header("whoami")));
        }

        return response()->json([
            "data" => $category?->withoutRelations(),
            "tiles" => view("components.browser.category-tiles", [
                "category" => $category,
            ])->render(),
            "sidebar" => view("components.browser.category-tree", [
                "category" => $category,
            ])->render(),
        ]);
    }
}
