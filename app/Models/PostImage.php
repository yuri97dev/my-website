<?php

namespace App\Models;

use App\Concerns\OptimizesStoredImage;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostImage extends Model
{
    use HasUuids;
    use OptimizesStoredImage;

    protected $fillable = [
        "post_id",
        "path",
        "size_bytes",
        "original_size_bytes",
        "optimization_attempts",
        "optimized_at",
    ];

    protected function casts(): array
    {
        return [
            "optimized_at" => "datetime",
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
