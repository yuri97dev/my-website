<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostView extends Model
{
    protected $fillable = [
        "post_id",
        "visitor_hash",
        "user_id",
        "referrer",
        "viewed_at",
    ];

    protected function casts(): array
    {
        return [
            "viewed_at" => "datetime",
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
