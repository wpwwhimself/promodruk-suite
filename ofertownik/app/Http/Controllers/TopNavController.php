<?php

namespace App\Http\Controllers;

use App\Models\Shipyard\StandardPage;
use Illuminate\Http\Request;

class TopNavController extends Controller
{
    public function show(string $slug)
    {
        $page = StandardPage::all()->firstWhere(fn ($p) => $p->slug == $slug);
        return view("top-nav-page", ["page" => $page]);
    }
}
