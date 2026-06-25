<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\AiProvider\StoreAiProviderRequest;
use App\Http\Requests\AiProvider\UpdateAiProviderRequest;
use App\Models\AiProvider;

class AiProviderController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min($request->integer("per_page", 15), 100);

        $paginator = AiProvider::query()
            ->select([
                "id",
                "name",
                "provider",
                "url",
                "is_enabled",
                "created_at",
                "updated_at",
            ])
            ->orderByDesc("id")
            ->paginate($perPage);

        return response()->json([
            "current_page" => $paginator->currentPage(),
            "last_page" => $paginator->lastPage(),
            "per_page" => $paginator->perPage(),
            "total" => $paginator->total(),
            "ai_providers" => $paginator->items(),
        ], 200);
    }

    public function store(StoreAiProviderRequest $request)
    {
        $aiProvider = AiProvider::create($request->validated());

        return response()->json([
            "ai_provider" => $aiProvider,
        ], 201);
    }

    public function update(UpdateAiProviderRequest $request, AiProvider $aiProvider)
    {
        $aiProvider->update($request->validated());

        return response()->json([
            "ai_provider" => $aiProvider->fresh(),
        ], 200);
    }
}
