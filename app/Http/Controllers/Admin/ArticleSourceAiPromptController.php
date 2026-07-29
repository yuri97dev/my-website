<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ArticleSource\SyncArticleSourceAiPromptsRequest;
use App\Models\ArticleSource;

class ArticleSourceAiPromptController extends Controller
{
    public function index(ArticleSource $articleSource)
    {
        $prompts = $articleSource->aiPrompts()
            ->select([
                "ai_prompts.id",
                "ai_prompts.ai_provider_id",
                "ai_prompts.key",
                "ai_prompts.model",
            ])
            ->with("aiProvider:id,name")
            ->get();

        return response()->json([
            "ai_prompts" => $prompts,
        ], 200);
    }

    public function sync(SyncArticleSourceAiPromptsRequest $request, ArticleSource $articleSource)
    {
        $payload = collect($request->validated("ai_prompt_ids"))
            ->mapWithKeys(fn ($id, $index) => [$id => ["position" => $index + 1]])
            ->all();

        $articleSource->aiPrompts()->sync($payload);

        return response()->json([
            "message" => "Prompts de IA atualizados com sucesso.",
        ], 200);
    }
}
