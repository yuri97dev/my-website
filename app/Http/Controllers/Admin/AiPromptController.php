<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AiPrompt\StoreAiPromptRequest;
use App\Http\Requests\AiPrompt\UpdateAiPromptRequest;
use App\Models\AiPrompt;
use App\Models\AiProvider;

class AiPromptController extends Controller
{
    private const TEMPLATES = [
        "post-translation-01-localization" => [
            "key" => "post-translation-01-localization",
            "path" => "prompts/post-translation-01-localization.txt",
        ],
        "post-translation-02-editorial" => [
            "key" => "post-translation-02-editorial",
            "path" => "prompts/post-translation-02-editorial.txt",
        ],
    ];

    public function template(string $name)
    {
        if( !isset(self::TEMPLATES[$name]) ) return response()->json([
            "message" => "Template não encontrado.",
        ], 404);

        $template = self::TEMPLATES[$name];
        $path = resource_path($template["path"]);

        if( !is_file($path) ) return response()->json([
            "message" => "Arquivo do template não encontrado.",
        ], 404);

        return response()->json([
            "key" => $template["key"],
            "content" => file_get_contents($path),
        ]);
    }

    public function index()
    {
        $prompts = AiPrompt::query()
            ->select([
                "id",
                "ai_provider_id",
                "key",
                "model",
            ])
            ->with("aiProvider:id,name")
            ->get()
            ->sortBy([
                fn ($prompt) => $prompt->aiProvider->name ?? "",
                fn ($prompt) => $prompt->key,
            ])
            ->values();

        return response()->json([
            "ai_prompts" => $prompts,
        ], 200);
    }

    public function forProvider(AiProvider $ai_provider)
    {
        $prompts = $ai_provider->prompts()
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

    public function store(StoreAiPromptRequest $request, AiProvider $ai_provider)
    {
        $ai_prompt = $ai_provider->prompts()->create($request->validated());

        return response()->json([
            "ai_prompt" => $ai_prompt,
        ], 201);
    }

    public function update(UpdateAiPromptRequest $request, AiPrompt $ai_prompt)
    {
        $ai_prompt->update($request->validated());

        return response()->json([
            "ai_prompt" => $ai_prompt->fresh(),
        ], 200);
    }

    public function destroy(AiPrompt $ai_prompt)
    {
        $ai_prompt->delete();

        return response()->json([
            "message" => "Prompt removido com sucesso.",
        ], 200);
    }
}
