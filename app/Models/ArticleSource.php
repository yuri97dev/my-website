<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArticleSource extends Model
{
    public const DRIVER_RSS = "rss";
    public const DRIVER_BROWSER = "browser";
    public const DRIVER_JSON = "json";

    protected $fillable = [
        "driver",
        "name",
        "locale",
        "http_method",
        "list_url",
        "list_item_selector",
        "item_title_selector",
        "item_link_selector",
        "item_link_base_url",
        "content_selector",
        "user_agent",
        "proxy_server",
        "accept_language",
        "is_enabled",
    ];

    protected function casts(): array
    {
        return [
            "is_enabled" => "boolean",
        ];
    }

    public function articles(): HasMany
    {
        return $this->hasMany(ScrapedArticle::class);
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where("is_enabled", true);
    }

    public function scopeDriver(Builder $query, string $driver): Builder
    {
        return $query->where("driver", $driver);
    }

    public function resolveItemLink(?string $link): string
    {
        return ($this->item_link_base_url ?? "") . ($link ?? "");
    }
}
