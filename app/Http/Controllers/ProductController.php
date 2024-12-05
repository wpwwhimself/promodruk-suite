<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\MainAttribute;
use App\Models\Product;
use App\Models\ProductFamily;
use App\Models\ProductMarking;
use App\Models\ProductSynchronization;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function getAttributes(int $id = null)
    {
        $data = ($id)
            ? Attribute::with("variants")->findOrFail($id)
            : Attribute::with("variants")->get();
        return response()->json($data);
    }

    public function getProducts(string $id = null, bool $soft = false)
    {
        $data = ($id)
            ? ($soft
                ? Product::with("attributes.variants", "stock")->where("id", "like", "%$id%")->get()
                : Product::with("attributes.variants", "stock")->findOrFail($id)
            )
            : Product::with("attributes.variants")->get();
        return response()->json($data);
    }
    public function getProductsByIds(Request $rq)
    {
        if ($rq->missing("ids")) abort(400, "No product IDs supplied");
        $data = Product::with(["attributes.variants", "markings", "productFamily"])
            ->whereIn("id", $rq->get("ids"))
            ->get();
        return response()->json($data);
    }

    public function getProductsForImport(string $supplier, string $category = null, string $query = null)
    {
        $data = collect();
        if ($category === "---") $category = null;
        foreach (explode(";", $supplier) as $prefix) {
            if ($category || $query) {
                // all matching products
                $d = Product::with(["attributes.variants", "productFamily"])
                    ->where("id", "like", "$prefix%")
                    ->where(function ($q) use ($category, $query) {
                        if ($category)
                            $q = $q->whereHas("productFamily", fn ($qq) => $qq->where("original_category", $category));
                        if ($query)
                            foreach(explode(";", $query) as $qstr) $q = $q->orWhere("id", "like", "%$qstr%");
                        return $q;
                    });
            } else {
                // only categories
                $d = ProductFamily::where("id", "like", "$prefix%")
                    ->select("original_category")
                    ->distinct();
            }
            $data = $data->merge($d->get());
        }
        return response()->json($data);
    }
    public function getProductsForRefresh(Request $rq)
    {
        if (empty($rq->get("ids"))) return response("No product IDs supplied", 400);

        $products = Product::with(["attributes.variants", "productFamily"])
            ->whereIn("id", $rq->get("ids"))
            ->get();
        $missing = collect($rq->get("ids"))->diff($products->pluck("id"));

        return response()->json(compact(
            "products",
            "missing",
        ));
    }

    public function getProductsForMarkings()
    {
        $data = Product::with(["productFamily"])
            ->whereHas("productFamily", fn ($q) => $q->whereIn("source", request("suppliers")))
            ->where(fn($q) => $q
                ->where("id", "like", "%".request("q", "")."%")
                ->orWhere("name", "like", "%".request("q", "")."%")
                ->orWhere("original_color_name", "like", "%".request("q", "")."%")
            )
            ->orderBy("id")
            ->selectRaw("id, CONCAT(name, ' | ', original_color_name, ' (', id, ')') as text")
            ->get()
            ->toArray();

        return response()->json([
            "results" => $data,
        ]);
    }

    public function getMainAttributes(int $id = null)
    {
        $data = ($id)
            ? MainAttribute::findOrFail($id)
            : MainAttribute::get();
        return response()->json($data);
    }

    public function getSuppliers()
    {
        $data = ProductSynchronization::select("supplier_name")->get()
            ->map(function ($s) {
                $handlerName = "App\DataIntegrators\\" . $s["supplier_name"] . "Handler";
                $handler = new $handlerName();
                return [
                    "name" => $s["supplier_name"],
                    "prefix" => $handler->getPrefix(),
                ];
            })
            ->push(["name" => "— produkty własne —", "prefix" => AdminController::CUSTOM_PRODUCT_PREFIX]);
        return response()->json($data);
    }

    public function getColorTile(string $color_name)
    {
        return view("components.color-tag", ["color" => MainAttribute::where("name", $color_name)->firstOrFail()]);
    }

    public function getProductColors(Request $rq)
    {
        $colors = ($rq->has("families"))
            ? ProductFamily::whereIn("id", $rq->get("families"))
                ->get()
                ->mapWithKeys(fn ($f) => [$f->id => $f->products->map(fn ($p) => $p->color)])
            : Product::whereIn("id", $rq->get("products"))
                ->get()
                ->mapWithKeys(fn ($p) => [$p->id => $p->color]);
        return response()->json(compact(
            "colors",
        ));
    }
}
