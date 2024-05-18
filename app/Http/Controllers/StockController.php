<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class StockController extends Controller
{
    public function stockDetails(string $product_code) {
        return Inertia::render("StockDetails", compact(
            "product_code",
        ));
    }
}
