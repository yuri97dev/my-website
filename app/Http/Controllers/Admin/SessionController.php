<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\UserAgent;

class SessionController extends Controller
{
    public function index(Request $request)
    {
        $currentSessionId = $request->session()->getId();
        $perPage = min($request->integer("per_page", 15), 100);

        $paginator = DB::table("sessions")
            ->leftJoin("users", "sessions.user_id", "=", "users.id")
            ->select([
                "sessions.id",
                "sessions.user_id",
                "sessions.ip_address",
                "sessions.user_agent",
                "sessions.last_activity",
                "users.name as user_name",
                "users.email as user_email",
            ])
            ->orderByDesc("sessions.last_activity")
            ->paginate($perPage);

        $sessions = collect($paginator->items())
            ->map(function ($session) use ($currentSessionId) {
                return array_merge(
                    [
                        "id" => $session->id,
                        "user_id" => $session->user_id,
                        "user_name" => $session->user_name,
                        "user_email" => $session->user_email,
                        "ip_address" => $session->ip_address,
                        "is_current" => $session->id === $currentSessionId,
                        "last_activity" => date("c", $session->last_activity),
                    ],
                    UserAgent::parse($session->user_agent),
                );
            })
            ->values()
            ->all();

        return response()->json([
            "current_page" => $paginator->currentPage(),
            "last_page" => $paginator->lastPage(),
            "per_page" => $paginator->perPage(),
            "total" => $paginator->total(),
            "sessions" => $sessions,
        ], 200);
    }
}
