<?php

namespace App\Models;

use App\Concerns\OptimizesStoredImage;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ScrapedArticleImage extends Model
{
    use HasUuids;
    use OptimizesStoredImage;

    protected $fillable = [
        "scraped_article_id",
        "path",
        "size_bytes",
        "original_size_bytes",
        "optimization_attempts",
        "optimized_at",
    ];

    protected function casts(): array
    {
        return [
            "optimized_at" => "datetime",
        ];
    }

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
