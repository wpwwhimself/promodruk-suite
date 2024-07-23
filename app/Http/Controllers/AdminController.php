<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public static $pages = [
        ["Ogólne", "dashboard"],
    ];

    public static $updaters = [
    ];

    /////////////// pages ////////////////

    public function dashboard()
    {
        $x = "";

        return view("admin.dashboard", compact(
            "x"
        ));
    }

    /////////////// updaters ////////////////

}
