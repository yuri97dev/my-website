<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchEmbeddingCache extends Model
{
    protected $fillable = [
        "normalized_query",
        "embedding_model",
        "embedding",
    ];
}
