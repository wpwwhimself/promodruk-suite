<?php

namespace App\Http\Controllers;

use App\Models\Supervisor;
use Illuminate\Http\Request;

class SupervisorController extends Controller
{
    public function edit(?int $id = null)
    {
        $supervisor = $id ? Supervisor::findOrFail($id) : null;

        return view("admin.supervisor-edit", compact(
            "supervisor",
        ));
    }

    public function submit(Request $rq)
    {
        if ($rq->method == "DELETE") {
            Supervisor::findOrFail($rq->id)->delete();
        } else {
            $form_data = [
                ...$rq->except(["_token", "method", "visible"]),
                "visible" => $rq->has("visible"),
            ];
            Supervisor::updateOrCreate(["id" => $rq->id], $form_data);
        }

        return redirect()->route("settings")->with("success", "Opiekun zaktualizowany");
    }
}
