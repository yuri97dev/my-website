<?php

namespace App\Http\Controllers\Blog;

use App\Actions\RecordPostView;
use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;

class PostViewController extends Controller
{
    public function store(Request $request, Post $post, RecordPostView $recordPostView)
    {
        $recordPostView($post, $request);
        return response()->noContent();
    }
}
