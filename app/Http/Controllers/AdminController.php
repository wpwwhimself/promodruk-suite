<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public function index()
    {
        $general_settings = Setting::where("group", "general")->get();
        [$welcome_text_content, $welcome_text_visible] = Setting::where("group", "welcome_text")->get();

        return view("admin.dashboard", compact(
            "general_settings",
            "welcome_text_content",
            "welcome_text_visible",
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

    public function updateWelcomeText(Request $rq)
    {
        Setting::find("welcome_text_content")->update(["value" => $rq->welcome_text_content]);
        Setting::find("welcome_text_visible")->update(["value" => $rq->welcome_text_visible]);
        return redirect()->back()->with("success", "Tekst powitalny zaktualizowany");
    }
}
