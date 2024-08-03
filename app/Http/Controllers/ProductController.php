<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
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
            ? Category::findOrFail($id)
            : Category::all();

        return response()->json($data);
    }

    public function listCategory(Category $category)
    {
        $products = $category->products()->paginate(25);
        return view("products", compact(
            "category",
            "products",
        ));
    }

    public function listSearchResults(string $query)
    {
        $results = Product::where("name", "like", "%" . $query . "%")
            ->orWhere("id", "like", "%" . $query . "%")
            ->paginate(25);

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
        $mainAttributeVariants = $product->family
            ->filter(fn ($p) => $p->main_attribute_id);

        return view("product", compact(
            "product",
            "mainAttributes",
            "mainAttributeVariants",
        ));
    }
}
