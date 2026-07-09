<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ScrapeHttpProfile extends Model
{
    protected $fillable = [
        "name",
        "headers",
        "is_enabled",
    ];

    protected function casts(): array
    {
        return [
            "headers" => "array",
            "is_enabled" => "boolean",
        ];
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where("is_enabled", true);
    }
}
