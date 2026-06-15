<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostImage extends Model
{
    use HasUuids;

    protected $fillable = [
        "post_id",
        "path",
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
