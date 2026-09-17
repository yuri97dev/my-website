<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $per_page = min($request->integer("per_page", 15), 100);

        $paginator = User::query()
            ->with("roles:id,slug,name")
            ->orderByDesc("id")
            ->paginate($per_page);

        return response()->json([
            "current_page" => $paginator->currentPage(),
            "last_page" => $paginator->lastPage(),
            "per_page" => $paginator->perPage(),
            "total" => $paginator->total(),
            "users" => $paginator->items(),
        ], 200);
    }
}
