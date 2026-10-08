<?php

namespace App\Http\Controllers\Laya;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Http;

class LayaController extends Controller
{
    public function index(Request $request)
    {
        $response = Http::baseUrl(config("services.laya.url"))
            ->withToken(config("services.laya.key"))
            ->connectTimeout(5)
            ->timeout(120)
            ->post("/v1/systemone", $request->post());

        return response()->json(
            $response->json(),
            $response->status()
        );
    }
}
