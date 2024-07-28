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
            ? Attribute::with("variants")->find($id)
            : Attribute::with("variants")->all();
        return response()->json($data);
    }

    public function getProducts(string $id = null)
    {
        $data = ($id)
            ? Product::with("attributes.variants")->find($id)
            : Product::with("attributes.variants")->all();
        return response()->json($data);
    }
}
