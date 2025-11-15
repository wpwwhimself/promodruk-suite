<?php

namespace App\Http\Controllers;

use App\Models\TopNavPage;
use Illuminate\Http\Request;

class TopNavController extends Controller
{
    public function show(string $slug)
    {
        $page = TopNavPage::all()->firstWhere(fn ($p) => $p->slug == $slug);
        return view("top-nav-page", ["page" => $page]);
    }
}
