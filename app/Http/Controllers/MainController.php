<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MainController extends Controller
{
    public function index() {
        return view("main");
    }

    public function goToStock(Request $rq) {
        return redirect()->route("stock", ["product_code" => $rq->product_code]);
    }
}
