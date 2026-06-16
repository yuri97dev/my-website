<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostChunk extends Model
{
    protected $fillable = [
        "post_id",
        "chunk_index",
        "content",
        "token_count",
        "embedding_model",
        "embedding",
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
