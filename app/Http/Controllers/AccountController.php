<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use App\Http\Requests\Account\DeleteAccountRequest;
use App\Http\Requests\Account\ForgotPasswordRequest;
use App\Http\Requests\Account\ResetPasswordRequest;
use App\Http\Requests\Account\UpdatePasswordRequest;
use App\Http\Requests\Account\UpdatePersonalDataRequest;
use App\Helpers\UserAgent;

class AccountController extends Controller
{
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $status = Password::sendResetLink(
            $request->only("email"),
        );

        if( $status === Password::RESET_LINK_SENT ) {
            return response()->json([
                "message" => "Um link para redefinir sua senha foi enviado para o seu e-mail.",
            ], 200);
        }

        if( $status === Password::RESET_THROTTLED ) {
            return response()->json([
                "message" => "Aguarde antes de tentar novamente.",
            ], 429);
        }

        return response()->json([
            "message" => "Falha ao enviar e-mail de recuperação",
        ], 500);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $status = Password::reset(
            $request->only("email", "password", "password_confirmation", "token"),
            function ($user, string $password) {
                $user->forceFill([
                    "password" => $password,
                ])->save();
            },
        );

        if( $status === Password::PASSWORD_RESET ) {
            return response()->json([
                "message" => "Senha redefinida com sucesso",
            ], 200);
        }

        return response()->json([
            "message" => "Link de redefinição inválido ou expirado.",
        ], 400);
    }

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
