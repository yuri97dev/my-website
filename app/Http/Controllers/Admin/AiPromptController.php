<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AiPrompt\StoreAiPromptRequest;
use App\Http\Requests\AiPrompt\UpdateAiPromptRequest;
use App\Models\AiPrompt;
use App\Models\AiProvider;

class AiPromptController extends Controller
{
    public function index(AiProvider $aiProvider)
    {
        $prompts = $aiProvider->prompts()
            ->select([
                "id",
                "ai_provider_id",
                "key",
                "model",
                "content",
                "created_at",
                "updated_at",
            ])
            ->orderBy("key")
            ->get();

        return response()->json([
            "ai_prompts" => $prompts,
        ], 200);
    }

    public function store(StoreAiPromptRequest $request, AiProvider $aiProvider)
    {
        $aiPrompt = $aiProvider->prompts()->create($request->validated());

        return response()->json([
            "ai_prompt" => $aiPrompt,
        ], 201);
    }

    public function update(UpdateAiPromptRequest $request, AiPrompt $aiPrompt)
    {
        $aiPrompt->update($request->validated());

        return response()->json([
            "ai_prompt" => $aiPrompt->fresh(),
        ], 200);
    }

    public function destroy(AiPrompt $aiPrompt)
    {
        $aiPrompt->delete();

        return response()->json([
            "message" => "Prompt removido com sucesso.",
        ], 200);
    }
}
