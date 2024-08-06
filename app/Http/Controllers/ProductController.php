<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\MainAttribute;
use App\Models\Product;
use App\Models\ProductSynchronization;
use Illuminate\Http\Request;

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

    public function getProductsForImport(string $supplier, string $category = null)
    {
        $data = collect();
        foreach (explode(";", $supplier) as $prefix) {
            $d = Product::with("attributes.variants")->where("id", "like", "$prefix%");
            if ($category) $d = $d->where("original_category", $category);
            $data = $data->merge($d->get());
        }
        return response()->json($data);
    }
    public function getProductsForRefresh(Request $rq)
    {
        if (empty($rq->get("ids"))) return response("No product IDs supplied", 400);

        $data = Product::with("attributes.variants")
            ->whereIn("id", $rq->get("ids"))
            ->get();

        return response()->json($data);
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
            });
        return response()->json($data);
    }
}
