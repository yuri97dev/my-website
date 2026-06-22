<?php

namespace App\Models;

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
        "is_enabled",
    ];

    protected function casts(): array
    {
        return [
            "is_enabled" => "boolean",
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
}
