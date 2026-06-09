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
                $data = (array) $session;

                unset($data["user_agent"]);

                return array_merge(
                    $data,
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
