<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
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
        $products = $category->products
            ->groupBy("product_family_id")
            ->map(fn ($group) => $group->random());
        $products = new LengthAwarePaginator(
            $products,
            $products->count(),
            25
        );

        return view("products", compact(
            "category",
            "products",
        ));
    }

    public function listSearchResults(string $query)
    {
        $results = Product::where("name", "like", "%" . $query . "%")
            ->orWhere("id", "like", "%" . $query . "%")
            ->paginate(25)
            ->withQueryString();

        return view("search-results")->with([
            "query" => $query,
            "results" => $results,
        ]);
    }

    public function listProduct(string $id = null)
    {
        if (empty($id)) return redirect()->route('products');

        $product = Product::findOrFail($id);
        $mainAttributes = Http::get(env("MAGAZYN_API_URL") . "main-attributes")->collect();
        $mainAttributeVariants = $product->family->whereNotNull("original_color_name");

        return view("product", compact(
            "product",
            "mainAttributes",
            "mainAttributeVariants",
        ));
    }
}
