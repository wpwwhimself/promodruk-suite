<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function getCategory(int $id = null)
    {
        $data = ($id)
            ? Category::findOrFail($id)
            : Category::all();

        return response()->json($data);
    }
}
