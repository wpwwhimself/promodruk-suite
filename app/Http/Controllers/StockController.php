<?php

namespace App\Http\Controllers;

use App\DataIntegrators\AsgardHandler;
use App\DataIntegrators\AxpolHandler;
use App\DataIntegrators\EasygiftsHandler;
use App\DataIntegrators\MidoceanHandler;
use App\DataIntegrators\PARHandler;
use App\Models\Stock;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public static function stockDetails(string $product_code)
    {
        $data = collect();

        try {
            foreach([
                new AsgardHandler(),
                new MidoceanHandler(),
                new PARHandler(),
                new EasygiftsHandler(),
            ] as $handler) {
                $data = $data->merge($handler->getDataWithPrefix($product_code));
            }
        } catch (Exception $ex) {
            throw $ex;
        }

        return compact(
            "product_code",
            "data",
        );
    }

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
    public function stockJson(string $product_code)
    {
        $data = collect();
        foreach (explode(";", $product_code) as $code) {
            $data = $data->merge(
                Stock::where("id", "like", $code)
                    ->orderBy("id")
                    ->get()
            );
        }

        return response()->json($data);
    }
}
