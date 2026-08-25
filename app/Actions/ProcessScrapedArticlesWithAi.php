<?php

namespace App\Actions;

use App\Models\ScrapedArticle;

class ProcessScrapedArticlesWithAi
{
    public function __invoke(): void
    {
        $article = ScrapedArticle::query()
            ->enabled()
            ->whereNull("translation_rejected_at")
            ->whereNull("translated_content")
            ->where("translation_attempts", "<", TranslateScrapedArticle::MAX_TRANSLATION_ATTEMPTS)
            ->whereHas("source.aiPrompts")
            ->whereDoesntHave("source.aiPrompts.aiProvider", fn ($query) => $query->where("is_enabled", false))
            ->orderBy("id")
            ->first();

        if( !$article ) {
            return;
        }

        (new TranslateScrapedArticle)($article);
    }
}
