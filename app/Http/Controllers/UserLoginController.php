<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use DB;
use File;
use Mail;
use App\Mail\AdminForgetMail;

class UserLoginController extends Controller
{

    public function show()
    {
        if (Auth::check()) {
            Session()->put('userdata', Auth::user());

            return redirect()->route('index');
        }

        return view('theme.login');
    }
    public function checklogin(Request $request)
    {
        $adata = User::where('email', $request->email)->first();

        if ($adata && Hash::check($request->password, $adata->password)) {
            Auth::login($adata, true);
            Session()->put('userdata', $adata);

            $request->session()->regenerate();

            return redirect()->route('index');
        } elseif ($adata) {
            return redirect('/userlogin')->with('error', 'Password Incorrect');
        } else {
            return redirect('/userlogin')->with('error', 'User Not Found');
        }
    }
}
