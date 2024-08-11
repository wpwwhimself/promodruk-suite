<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

class ProductController extends Controller
{
    public function home()
    {
        $categories = Category::whereNull("parent_id")
            ->orderBy("ordering")
            ->get();

        return view("main", compact(
            "categories",
        ));
    }

    public function getCategory(int $id = null)
    {
        $data = ($id)
            ? Category::with("children")->findOrFail($id)
            : Category::with("children")->get();

        return response()->json($data);
    }

    public function listCategory(Category $category)
    {
        $perPage = request("perPage", 100);
        $sortBy = request("sortBy", "price");

        $products = $category->products
            ->groupBy("product_family_id")
            ->map(fn ($group) => $group->random());

        if (Str::startsWith($sortBy, "-")) $products = $products->sortByDesc(Str::afterLast($sortBy, "-"));
        else $products = $products->sortBy($sortBy);

        $products = new LengthAwarePaginator(
            $products->slice($perPage * (request("page") - 1), $perPage),
            $products->count(),
            $perPage,
            request("page"),
            ["path" => ""]
        );

        return view("products", compact(
            "category",
            "products",
            "perPage",
            "sortBy",
        ));
    }

    public function listSearchResults(string $query)
    {
        $perPage = request("perPage", 100);
        $sortBy = request("sortBy", "price");

        $results = Product::where("name", "like", "%" . $query . "%")
            ->orWhere("id", "like", "%" . $query . "%")
            ->get()
            ->groupBy("product_family_id")
            ->map(fn ($group) => $group->random());

        if (Str::startsWith($sortBy, "-")) $results = $results->sortByDesc(Str::afterLast($sortBy, "-"));
        else $results = $results->sortBy($sortBy);

        $results = new LengthAwarePaginator(
            $results->slice($perPage * (request("page") - 1), $perPage),
            $results->count(),
            $perPage,
            request("page"),
            ["path" => ""]
        );

        return view("search-results", compact(
            "query",
            "results",
            "perPage",
            "sortBy",
        ));
    }

    public function listProduct(string $id = null)
    {
        if (empty($id)) return redirect()->route('products');

        $product = Product::findOrFail($id);

        return view("product", compact(
            "product",
        ));
    }
}
