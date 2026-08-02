<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScrapedArticleTranslationCache extends Model
{
    protected $fillable = [
        "scraped_article_id",
        "ai_prompt_id",
        "content",
    ];

    public function scrapedArticle(): BelongsTo
    {
        return $this->belongsTo(ScrapedArticle::class);
    }

    public function aiPrompt(): BelongsTo
    {
        return $this->belongsTo(AiPrompt::class);
    }
}
