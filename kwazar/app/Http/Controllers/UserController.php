<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function list()
    {
        $users = User::orderBy("name")->get();

        return view("pages.users.list", compact(
            "users",
        ));
    }

    public function edit(int $id = null)
    {
        if (!userIs("technical") && Auth::id() != $id) abort(403);

        $user = $id
            ? User::find($id)
            : null;
        $roles = Role::all();

        // nobody can edit super but super
        if ($user?->login == "super" && Auth::id() != $user?->id) abort(403);

        return view("pages.users.edit", compact(
            "user",
            "roles",
        ));
    }

    public function process(Request $rq)
    {
        $form_data = $rq->except(["_token", "roles"]);
        if (!$rq->id) {
            $form_data["password"] = $rq->login;
        }

        $user = User::updateOrCreate(
            ["id" => $rq->id],
            $form_data
        );
        $user->roles()->sync($rq->roles);

        return redirect()->route("users.list")->with("success", "Dane użytkownika zmienione");
    }

    public function resetPassword(int $user_id)
    {
        $user = User::find($user_id);
        $user->update(["password" => $user->login]);

        return back()->with("success", "Hasło użytkownika zresetowane");
    }
}
