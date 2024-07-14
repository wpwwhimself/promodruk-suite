<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index()
    {
        $available_settings = Setting::where("group", "general")->get();

        return view("admin.dashboard", compact(
            "available_settings",
        ));
    }

    public function updateSettings(Request $rq)
    {
        foreach ($rq->except("_token") as $name => $value) {
            Setting::find($name)->update(["value" => $value]);
        };

        return redirect()->back()->with("success", "Ustawienia zostały zaktualizowane");
    }
}
