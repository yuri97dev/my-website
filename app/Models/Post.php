<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = "draft";
    public const STATUS_PUBLISHED = "published";
    public const STATUS_ARCHIVED = "archived";

    protected $fillable = [
        "author_id",
        "post_category_id",
        "slug",
        "title",
        "status",
        "excerpt",
        "content",
        "published_at",
        "meta_description",
        "thumbnail_path",
    ];

    protected function casts(): array
    {
        return [
            "published_at" => "datetime",
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, "author_id");
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class, "post_category_id");
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(PostTag::class, "post_tag", "post_id", "post_tag_id");
    }

    public function images(): HasMany
    {
        return $this->hasMany(PostImage::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where("status", self::STATUS_PUBLISHED)
            ->whereNotNull("published_at")
            ->where("published_at", "<=", now());
    }
}
