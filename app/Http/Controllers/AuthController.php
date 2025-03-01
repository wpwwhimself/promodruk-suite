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

                if ($user->name == $credentials) return redirect()->route("change-password")->with("success", "Zalogowano, ale...");

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

    #region change password
    public function changePassword()
    {
        return view("auth.change-password");
    }

    public function processChangePassword(Request $rq)
    {
        $this->validate($rq, [
            "password" => "required|min:8|confirmed"
        ]);

        User::find(Auth::id())->update([
            "password" => Hash::make($rq->password)
        ]);

        return redirect()->route("dashboard")->with("success", "Hasło zostało zmienione");
    }
    #endregion
}
