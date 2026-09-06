<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\Verified;
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
use App\Models\User;

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

    public function verifyEmail(Request $request, string $id, string $hash)
    {
        $user = User::find($id);

        if( !$user || !hash_equals(sha1($user->getEmailForVerification()), $hash) ) {
            return redirect()
                ->route("login")
                ->with("error", "Link de verificação inválido.");
        }

        if( !$user->canLogin() ) {
            return redirect()
                ->route("login")
                ->with("error", "Link de verificação inválido.");
        }

        if( !$user->hasVerifiedEmail() ) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()
            ->route("account")
            ->with("success", "E-mail verificado com sucesso.");
    }

    public function sendEmailVerificationNotification(Request $request)
    {
        if( $request->user()->hasVerifiedEmail() ) {
            return response()->json([
                "message" => "E-mail já verificado.",
            ], 400);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json([
            "message" => "Link de verificação enviado para o seu e-mail.",
        ], 200);
    }

    public function updatePersonalData(UpdatePersonalDataRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();

        if( $validated["email"] !== $user->email ) {
            $email_exists = User::where("email", $validated["email"])->exists();

            if( $email_exists ) return response()->json([
                "message" => "Este e-mail já está em uso.",
            ], 422);
        }

        $user->fill($validated);

        if( $user->isDirty("email") ) {
            $user->email_verified_at = null;
        }

        $user->save();

        if( $user->wasChanged("email") ) {
            $user->sendEmailVerificationNotification();
        }

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
        $current_session_id = $request->session()->getId();

        $sessions = DB::table("sessions")
            ->where("user_id", $request->user()->id)
            ->orderByDesc("last_activity")
            ->get()
            ->map(function ($session) use ($current_session_id) {
                return array_merge(
                    [
                        "id" => $session->id,
                        "ip_address" => $session->ip_address,
                        "is_current" => $session->id === $current_session_id,
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
