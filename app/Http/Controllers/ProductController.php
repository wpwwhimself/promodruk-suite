<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

class ProductController extends Controller
{
    public function home()
    {
        $categories = Category::whereNull("parent_id")
            ->where("visible", ">=", Auth::id() ? 1 : 2)
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

        //* active filters *//
        $colorsForFiltering = $products->pluck("color")
            ->filter(fn ($c) => $c["color"] ?? null) // only primary colors
            ->sortBy("name");
        if ($products->pluck("color")->count() != $colorsForFiltering->count()) {
            $colorsForFiltering = $colorsForFiltering->push([
                "id" => 0,
                "name" => "pozostałe",
                "color" => null,
            ]);
        }
        $colorsForFiltering = $colorsForFiltering->unique()->toArray();

        $extraFiltrables = $products
            ->pluck("extra_filtrables")
            ->filter()
            ->reduce(fn ($all, $extra) => $all->mergeRecursive($extra), collect())
            ->map(fn ($extra) => collect($extra)
                ->unique()
                ->sort()
                ->merge("pozostałe")
                ->toArray()
            );

        foreach ($filters as $prop => $val) {
            if (empty($val)) continue;

            switch ($prop) {
                case "color":
                    $products = $products->filter(fn ($p) => collect(explode("|", $val))->reduce(
                        fn ($total, $val_item) => $total || (
                            ($val_item == "pozostałe")
                                ? $p->color["color"] == null
                                : Str::contains($p->color["name"], $val_item)
                        ),
                        false
                    ));
                    break;
                case "availability":
                    $stock_data = Http::get(env("MAGAZYN_API_URL") . "stock")->collect();
                    $products = $products->filter(fn ($p) => $stock_data->firstWhere("id", $p->id)["current_stock"] > 0);
                    break;
                case "prefix":
                    $products = $products->filter(fn ($p) => collect(preg_split("/[|\/]/", $val))->reduce(
                        fn ($total, $val_item) => $total || Str::of($p->front_id)->startsWith($val_item)
                    ));
                    break;
                case "extra":
                    foreach ($val as $extra_prop => $extra_val) {
                        if (empty($extra_val)) continue;

                        $products = $products->filter(fn ($p) => collect(explode("|", $extra_val))->reduce(
                            fn ($total, $val_item) => $total || (
                                ($val_item == "pozostałe")
                                    ? empty($p->extra_filtrables[$extra_prop])
                                    : in_array($val_item, $p->extra_filtrables[$extra_prop] ?? [])
                            ),
                            false
                        ));
                    }
                    break;
                default:
                    $products = $products->where($prop, "=", $val);
            }
        }

        //* available filters *//
        $colorsForFiltering = $products->pluck("color")
            ->filter(fn ($c) => $c["color"]) // only primary colors
            ->sortBy("name");
        if ($products->pluck("color")->count() != $colorsForFiltering->count()) {
            $colorsForFiltering = $colorsForFiltering->push([
                "id" => 0,
                "name" => "pozostałe",
                "color" => null,
            ]);
        }
        $colorsForFiltering = $colorsForFiltering->unique()->toArray();

        // find all prefixes in current product list
        $prefixes = Http::get(env("MAGAZYN_API_URL") . "suppliers")->collect()
            ->pluck("prefix")
            ->sortDesc();
        $product_ids = $products->pluck("front_id");
        $prefixesForFiltering = collect();
        foreach ($product_ids as $id) {
            if (Str::startsWith($id, $prefixesForFiltering->flatten())) continue;

            // run full list one by one (longest to shortest within alphabetical)
            foreach ($prefixes as $prfx) {
                if (Str::startsWith($id, $prfx)) {
                    $prefixesForFiltering->push($prfx);
                    break;
                }
            }
        }
        $prefixesForFiltering = $prefixesForFiltering
            ->mapWithKeys(fn ($prfx) => [implode("/", collect($prfx)->toArray()) => implode("/", collect($prfx)->toArray())])
            ->sort();

        $extraFiltrables = $products
            ->pluck("extra_filtrables")
            ->filter()
            ->reduce(fn ($all, $extra) => $all->mergeRecursive($extra), collect())
            ->map(fn ($extra) => collect($extra)
                ->unique()
                ->sort()
                ->merge("pozostałe")
                ->toArray()
            );

        //* sorts *//
        $products = $products
            ->sort(fn ($a, $b) => sortByNullsLast(
                Str::afterLast($sortBy, "-"),
                $a, $b,
                Str::startsWith($sortBy, "-")
            ))
            ->groupBy("product_family_id");

        $products = new LengthAwarePaginator(
            $products->slice($perPage * (request("page", 1) - 1), $perPage),
            $products->count(),
            $perPage,
            request("page", 1),
            ["path" => ""]
        );

        return view("products", compact(
            "category",
            "products",
            "perPage",
            "sortBy",
            "filters",
            "extraFiltrables",
            "colorsForFiltering",
            "prefixesForFiltering",
        ));
    }

    public function listSearchResults(string $query)
    {
        $perPage = request("perPage", 100);
        $sortBy = request("sortBy", "price");
        $filters = request("filters", []);

        $results = Product::where("name", "like", "%" . $query . "%")
            ->orWhere("front_id", "like", "%" . $query . "%")
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
            ->filter(fn ($p) => $p->categories->count() > 0)
            ->groupBy("product_family_id")
            ->map(fn ($group) => $group->random())
            ->sort(fn ($a, $b) => sortByNullsLast(
                Str::afterLast($sortBy, "-"),
                $a, $b,
                Str::startsWith($sortBy, "-")
            ));

        // $results = new LengthAwarePaginator(
        //     $results->slice($perPage * (request("page", 1) - 1), $perPage),
        //     $results->count(),
        //     $perPage,
        //     request("page", 1),
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

    public function listProduct(?string $id = null)
    {
        if (empty($id)) return redirect()->route('products');

        $product = Product::where("front_id", $id)->first();

        if (empty($product->categories)) abort(404, "Produkt niedostępny");

        return view("product", compact(
            "product",
        ));
    }
}
