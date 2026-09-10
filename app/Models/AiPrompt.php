<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiPrompt extends Model
{
    protected $fillable = [
        "ai_provider_id",
        "key",
        "model",
        "content",
        "is_system",
    ];

    protected function casts(): array
    {
        return [
            "is_system" => "boolean",
        ];
    }

    public function aiProvider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class);
    }
}
