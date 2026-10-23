<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;

class ResetPasswordController extends Controller
{
    use ResetsPasswords;

    protected $redirectTo = '/';

    public function __construct()
    {
        $this->middleware('guest');
    }

    public function showResetForm(Request $request, $token = null)
    {
        $email = $request->email;
        $user = User::where('email', $email)->first();

        return view('auth.passwords.reset')->with([
            'token'        => $token,
            'email'        => $email,
            'userName'     => $user?->name,
            'blok'         => $user?->blok,
        ]);
    }
}
