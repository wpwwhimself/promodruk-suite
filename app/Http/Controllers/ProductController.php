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
        $filters = request("filters", []);

        $products = $category->products;

        $colorsForFiltering = $products->pluck("color")->unique()->sortBy("name")->pluck("name", "name");

        foreach ($filters as $prop => $val) {
            switch ($prop) {
                case "color":
                    $products = $products->filter(fn ($p) => $p->color["name"] == $val);
                    break;
                default:
                    $products = $products->where($prop, "=", $val);
            }
        }

        $products = $products
            ->groupBy("product_family_id")
            ->map(fn ($group) => $group->random())
            ->sort(fn ($a, $b) => sortByNullsLast(
                Str::afterLast($sortBy, "-"),
                $a, $b,
                Str::startsWith($sortBy, "-")
            ));

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
            "filters",
            "colorsForFiltering",
        ));
    }

    public function listSearchResults(string $query)
    {
        $perPage = request("perPage", 100);
        $sortBy = request("sortBy", "price");
        $filters = request("filters", []);

        $results = Product::where("name", "like", "%" . $query . "%")
            ->orWhere("id", "like", "%" . $query . "%")
            ->get();

        // $colorsForFiltering = $results->pluck("color")->unique();

        foreach ($filters as $prop => $val) {
            switch ($prop) {
                case "color":
                    $results = $results->filter(fn ($p) => $p->color["name"] == $val);
                    break;
                default:
                    $results = $results->where($prop, "=", $val);
            }
        }

        $results = $results
            ->groupBy("product_family_id")
            ->map(fn ($group) => $group->random())
            ->sort(fn ($a, $b) => sortByNullsLast(
                Str::afterLast($sortBy, "-"),
                $a, $b,
                Str::startsWith($sortBy, "-")
            ));

        // $results = new LengthAwarePaginator(
        //     $results->slice($perPage * (request("page") - 1), $perPage),
        //     $results->count(),
        //     $perPage,
        //     request("page"),
        //     ["path" => ""]
        // );

        return view("search-results", compact(
            "query",
            "results",
            // "perPage",
            // "sortBy",
            // "filters",
            // "colorsForFiltering",
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
