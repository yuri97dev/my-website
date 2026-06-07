<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Account\DeleteAccountRequest;
use App\Http\Requests\Account\UpdatePasswordRequest;
use App\Http\Requests\Account\UpdatePersonalDataRequest;
use App\Helpers\UserAgent;

class AccountController extends Controller
{
    public function updatePersonalData(UpdatePersonalDataRequest $request)
    {
        $request->user()->update($request->validated());
        return response()->json([ "message" => "Dados pessoais atualizados com sucesso" ], 200);
    }

    public function updatePassword(UpdatePasswordRequest $request)
    {
        $request->user()->update([
            "password" => $request->validated("password"),
        ]);

        return response()->json([ "message" => "Senha alterada com sucesso" ], 200);
    }

    public function sessions(Request $request)
    {
        $currentSessionId = $request->session()->getId();

        $sessions = DB::table("sessions")
            ->where("user_id", $request->user()->id)
            ->orderByDesc("last_activity")
            ->get()
            ->map(function ($session) use ($currentSessionId) {
                return array_merge(
                    [
                        "id" => $session->id,
                        "ip_address" => $session->ip_address,
                        "is_current" => $session->id === $currentSessionId,
                        "last_activity" => date("c", $session->last_activity),
                    ],
                    UserAgent::parse($session->user_agent),
                );
            })
            ->values()
            ->all();

        return response()->json([ "sessions" => $sessions ], 200);
    }

    public function revokeSession(Request $request, string $session_id)
    {
        $is_current = $session_id === $request->session()->getId();

        if( $is_current ) return response()->json([
            "message" => "Não é possível revogar a sessão atual.",
        ], 400);

        $deleted = DB::table("sessions")
            ->where("id", $session_id)
            ->where("user_id", $request->user()->id)
            ->delete();

        if( !$deleted ) return response()->json([
            "message" => "Sessão não encontrada.",
        ], 404);

        return response()->json([ "message" => "Sessão revogada com sucesso." ], 200);
    }

    public function destroy(DeleteAccountRequest $request)
    {
        $user = $request->user();

        DB::transaction(function () use ($user) {
            DB::table("sessions")
                ->where("user_id", $user->id)
                ->delete();

            DB::table("password_reset_tokens")
                ->where("email", $user->email)
                ->delete();

            $user->tokens()->delete();
            $user->delete();
        });

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([ "message" => "Conta excluída com sucesso" ], 200);
    }
}
