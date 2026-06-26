<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Hidden(["api_key"])]

class AiProvider extends Model
{
    protected $fillable = [
        "name",
        "provider",
        "api_key",
        "is_enabled",
    ];

    protected function casts(): array
    {
        return [
            "api_key" => "encrypted",
            "is_enabled" => "boolean",
        ];
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where("is_enabled", true);
    }

    public function scopeProvider(Builder $query, string $provider): Builder
    {
        return $query->where("provider", $provider);
    }

    public function prompts(): HasMany
    {
        return $this->hasMany(AiPrompt::class);
    }
}
