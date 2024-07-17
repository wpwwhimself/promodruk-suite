<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

    public function updateLogo(Request $rq)
    {
        if ($rq->file("logo")->extension() !== "png") {
            return redirect()->back()->with("error", "Logo musi mieć rozszerzenie .png");
        }

        if (!$rq->file("logo")->storeAs(
            "meta",
            "logo.".$rq->file("logo")->extension(),
            "public"
        )) {
            return redirect()->back()->with("error", "Logo nie zostało zaktualizowane");
        }

        return redirect()->back()->with("success", "Logo zostało zaktualizowane");
    }
}
