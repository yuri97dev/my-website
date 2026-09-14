<?php

namespace App\Models;

use App\Actions\TranslateScrapedArticle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScrapedArticle extends Model
{
    protected $fillable = [
        "article_source_id",
        "post_id",
        "url",
        "title",
        "content",
        "translated_title",
        "translated_content",
        "translation_rejected_at",
        "translation_attempts",
        "processing_by",
        "processing_at",
        "assistant_selection_prompt",
        "is_enabled",
    ];

    protected function casts(): array
    {
        return [
            "is_enabled" => "boolean",
            "translation_rejected_at" => "datetime",
            "processing_at" => "datetime",
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (ScrapedArticle $article) {
            $article->images->each->delete();
        });
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where("is_enabled", true);
    }

    public function scopeEligibleForAssistant(Builder $query): Builder
    {
        return $query
            ->enabled()
            ->whereNull("translation_rejected_at")
            ->whereNull("translated_content")
            ->whereNull("post_id")
            ->whereNull("processing_by")
            ->where("translation_attempts", "<", TranslateScrapedArticle::MAX_TRANSLATION_ATTEMPTS)
            ->whereHas("source.aiPrompts")
            ->whereDoesntHave("source.aiPrompts.aiProvider", fn ($q) => $q->where("is_enabled", false));
    }

    public function isProcessedByAssistant(): bool
    {
        return $this->processing_by !== null
            && (int) $this->processing_by === User::ASSISTANT_ID;
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, "processing_by");
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(ArticleSource::class, "article_source_id");
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ScrapedArticleImage::class);
    }

    public function translationCaches(): HasMany
    {
        return $this->hasMany(ScrapedArticleTranslationCache::class);
    }
}
