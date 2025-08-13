<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EnMasseController extends Controller
{
    private function getItemsForModel(string $model, ?array $ids): Collection
    {
        $dbmodel = ($model == "ProductFamily") ? "App\Models\Product" : "App\Models\\".$model;
        $items = ($ids != [""])
            ? $dbmodel::whereIn(($model == "ProductFamily") ? "product_family_id" : "id", $ids)->get()
            : $dbmodel::all();
        return $items;
    }

    public function init(Request $rq)
    {
        $items = $this->getItemsForModel($rq->input("model"), explode(";", $rq->input("ids")));

        $operations = collect([
            [
                "op" => "set:visible",
                "name" => "Zmień widoczność",
                "type" => "radio",
                "options" => ["Publiczny" => 2, "Prywatny" => 1, "Ukryty" => 0]
            ],
            [
                "op" => "delete",
                "name" => "Usuń",
                "type" => "single"
            ],
        ]);

        $model = $rq->model;
        $ids = $rq->ids;

        return view("admin.en-masse.init", compact(
            "items",
            "operations",
            "model",
            "ids",
        ));
    }

    public function execute(Request $rq)
    {
        $items = $this->getItemsForModel($rq->input("model"), explode(";", $rq->input("ids")));
        foreach ($items as $item) {
            if (Str::startsWith($rq->operation, "set:")) {
                $prop = Str::after($rq->operation, "set:");
                $item->$prop = $rq->option;
                $item->save();
                continue;
            }

            if ($rq->operation == "delete") {
                $item->delete();
                continue;
            }
        }

        $redirect_model = ($rq->model == "ProductFamily") ? "Product" : $rq->model;
        return redirect()->route(Str::of($redirect_model)->plural()->slug()->toString())->with("success", "Operacja została wykonana");
    }
}
