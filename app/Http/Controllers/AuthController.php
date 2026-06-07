<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Auth\LoginRequest;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        $remember_me = boolval($request->remember_me);

        unset($credentials["remember_me"]);

        if( Auth::attempt($credentials, $remember_me) ) {
            $request->session()->regenerate();
            return response()->json([ "message"=> "Login successful" ], 200);
        }

        return response()->json([ "message"=> "Credenciais inválidas" ], 401);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([ "message"=> "Desconectado com sucesso" ], 200);
    }
}
