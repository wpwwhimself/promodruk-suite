<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\Product;
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
                ? Product::with("attributes.variants")->where("id", "like", "%$id%")->get()
                : Product::with("attributes.variants")->findOrFail($id)
            )
            : Product::with("attributes.variants")->get();
        return response()->json($data);
    }
}
