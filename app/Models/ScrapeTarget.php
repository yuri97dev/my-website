<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScrapeTarget extends Model
{
    public const DRIVER_RSS = "rss";
    public const DRIVER_BROWSER = "browser";

    protected $fillable = [
        "driver",
        "name",
        "locale",
        "http_method",
        "list_url",
        "list_item_selector",
        "item_title_selector",
        "item_link_selector",
        "content_selector",
        "is_enabled",
    ];

    protected function casts(): array
    {
        return [
            "is_enabled" => "boolean",
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(ScrapeItem::class);
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where("is_enabled", true);
    }

    public function scopeDriver(Builder $query, string $driver): Builder
    {
        return $query->where("driver", $driver);
    }
}
