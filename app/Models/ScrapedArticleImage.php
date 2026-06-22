<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ScrapedArticleImage extends Model
{
    use HasUuids;

    protected $fillable = [
        "scraped_article_id",
        "path",
    ];

    protected static function booted(): void
    {
        static::deleting(function (ScrapedArticleImage $image) {
            Storage::disk("local")->delete(ltrim($image->path, "/"));
        });
    }

    public function scrapedArticle(): BelongsTo
    {
        return $this->belongsTo(ScrapedArticle::class);
    }
}
