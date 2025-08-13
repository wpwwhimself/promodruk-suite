<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function input(){
        if (Auth::check()) return redirect(route("dashboard"));
        return view("auth.login");
    }

    public function authenticate(Request $rq){
        $credentials = $rq->except(["_token"]);

        if(Auth::attempt($credentials)){
            $rq->session()->regenerate();

            if ($rq->login == $rq->password) return view("auth.change-password");

            return redirect()->intended(route("dashboard"))->with("success", "Zalogowano");
        }

        return back()->with("error", "Nieprawidłowe dane logowania");
    }

    public function changePassword(Request $rq){
        $validator = Validator::make($rq->all(), [
            'password' => ['required', 'confirmed'],
        ]);
        if ($validator->fails()) return view("auth.change-password")->with("error", "Coś jest nie tak z hasłem");

        User::findOrFail(Auth::id())->update([
            "password" => Hash::make($rq->password),
        ]);
        return redirect(route("dashboard"))->with("success", "Hasło zostało zmienione");
    }

    public function logout(Request $request){
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect("/")->with("success", "Wylogowano");
    }
}
