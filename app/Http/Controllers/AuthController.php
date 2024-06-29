<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function input(){
        if (Auth::check()) return redirect(route("dashboard"));
        return view("auth.login");
    }

    public function authenticate(Request $rq){
        $credentials = trim($rq->password);

        $users = User::all();
        foreach($users as $user){
            if(Hash::check($credentials, $user->password)){
                Auth::login(User::find($user->id));
                $rq->session()->regenerate();
                return redirect()->intended(route("dashboard"))->with("success", "Zalogowano");
            }
        }

        return back()->with("error", "Nieprawidłowe dane logowania");
    }

    public function logout(Request $request){
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect("/")->with("success", "Wylogowano");
    }
}
