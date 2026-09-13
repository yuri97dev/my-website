<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        $remember_me = boolval($request->remember_me);

        unset($credentials["remember_me"]);

        if( Auth::attempt($credentials, $remember_me) ) {
            $user = Auth::user();

            if( !$user instanceof User || !$user->canLogin() ) {
                Auth::logout();
                return response()->json([ "message"=> "Credenciais inválidas" ], 401);
            }

            $request->session()->regenerate();

            return response()->json([
                "message" => "Login successful",
                "redirect" => redirect()->intended(route("home"))->getTargetUrl(),
            ], 200);
        }

        return response()->json([ "message"=> "Credenciais inválidas" ], 401);
    }

    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        $email_exists = User::where("email", $validated["email"])->exists();

        if( $email_exists ) return response()->json([
            "message" => "Não foi possível processar sua solicitação.",
        ], 422);

        $user = User::create($validated);

        $user->sendEmailVerificationNotification();

        Auth::login($user);

        $request->session()->regenerate();

        return response()->json([
            "message" => "Usuário registrado com sucesso",
            "redirect" => redirect()->intended(route("home"))->getTargetUrl(),
        ], 201);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([ "message"=> "Desconectado com sucesso" ], 200);
    }
}
