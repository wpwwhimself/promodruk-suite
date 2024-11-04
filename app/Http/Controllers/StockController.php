<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductFamily;
use App\Models\Stock;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function stock(string $product_code)
    {
        $data = collect();
        foreach (explode(";", $product_code) as $code) {
            $data = $data->merge(
                Stock::where("id", "like", $code)
                    ->orderBy("id")
                    ->get()
            );
        }

        return view("stock", array_merge(
            [
                "title" => implode(" | ", [$product_code, "Stan magazynowy"]),
                "now" => $data->min("updated_at"),
            ],
            compact(
                "data",
                "product_code",
            )
        ));
    }

    private function stockJsonFunnel(string $product_code = null, bool $strict = false)
    {
        $data = collect();

        if (empty($product_code))
            return response()->json(Stock::orderBy("id")->get());

        foreach (explode(";", $product_code) as $code) {
            $data = $data->merge(
                ProductFamily::where("id", "like", $strict ? "$code" : "%$code%")
                    ->first()
                    ?->source === null
                ? "custom"
                : Stock::where("stocks.id", "like", $strict ? "$code" : "%$code%")
                    ->orderBy("stocks.id")
                    ->leftJoin("products", "products.id", "=", "stocks.id")
                    ->get()
            );
        }

        return response()->json($data);
    }
    public function stockJson(string $product_code = null)
    {
        return $this->stockJsonFunnel($product_code);
    }
    public function stockJsonStrict(string $product_code)
    {
        return $this->stockJsonFunnel($product_code, true);
    }
}
