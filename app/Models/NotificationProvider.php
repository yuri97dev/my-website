<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Hidden(["config"])]

class NotificationProvider extends Model
{
    public const CHANNEL_DISCORD = "discord";

    public static function channels(): array
    {
        return [
            self::CHANNEL_DISCORD,
        ];
    }

    protected $fillable = [
        "key",
        "channel",
        "name",
        "config",
        "is_enabled",
    ];

    protected function casts(): array
    {
        return [
            "config" => "encrypted:array",
            "is_enabled" => "boolean",
        ];
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where("is_enabled", true);
    }

    public function scopeChannel(Builder $query, string $channel): Builder
    {
        return $query->where("channel", $channel);
    }

    public function scopeKey(Builder $query, string $key): Builder
    {
        return $query->where("key", $key);
    }
}
