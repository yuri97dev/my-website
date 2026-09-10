<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PostTag extends Model
{
    protected $fillable = [
        "slug",
        "name",
    ];

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, "post_tag", "post_tag_id", "post_id");
    }
}
