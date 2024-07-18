<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\TopNavPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public static $pages = [
        ["Ogólne", "dashboard"],
        ["Strony górne", "top-nav-pages"],
    ];

    public static $updaters = [
        "settings",
        "logo",
        "welcome-text",
        "top-nav-pages",
    ];

    /////////////// pages ////////////////

    public function dashboard()
    {
        $general_settings = Setting::where("group", "general")->get();
        [$welcome_text_content, $welcome_text_visible] = Setting::where("group", "welcome_text")->get();

        return view("admin.dashboard", compact(
            "general_settings",
            "welcome_text_content",
            "welcome_text_visible",
        ));
    }

    public function topNavPages()
    {
        $pages = TopNavPage::orderBy("ordering")->get();

        return view("admin.top-nav-pages", compact(
            "pages"
        ));
    }
    public function topNavPagesEdit(int $id = null)
    {
        $page = ($id) ? TopNavPage::findOrFail($id) : null;

        return view("admin.top-nav-page", compact(
            "page"
        ));
    }

    /////////////// updaters ////////////////

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

    public function updateTopNavPages(Request $rq)
    {
        $form_data = [
            "name" => $rq->name,
            "ordering" => $rq->ordering,
            "content" => $rq->content,
        ];

        if ($rq->mode == "save") {
            $page = (!$rq->id)
                ? TopNavPage::create($form_data)
                : TopNavPage::find($rq->id)->update($form_data);
            return redirect(route("top-nav-pages-edit", ["id" => $rq->id ?? $page->id]))->with("success", "Strona została zapisana");
        } else if ($rq->mode == "delete") {
            TopNavPage::find($rq->id)->delete();
            return redirect(route("top-nav-pages"))->with("success", "Strona została usunięta");
        } else {
            abort(400, "Updater mode is missing or incorrect");
        }
    }
}
