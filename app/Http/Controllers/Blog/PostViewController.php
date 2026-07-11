<?php

namespace App\Http\Controllers\Blog;

use App\Actions\RecordPostView;
use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostView;
use Illuminate\Http\Request;

class PostViewController extends Controller
{
    public function index(Request $request, Post $post)
    {
        $per_page = min($request->integer("per_page", 15), 100);

        $views_query = PostView::query()
            ->where("post_id", $post->id);

        $paginator = (clone $views_query)
            ->select([
                "id",
                "user_id",
                "referrer",
                "viewed_at",
            ])
            ->with("user:id,name")
            ->orderByDesc("viewed_at")
            ->paginate($per_page);

        return response()->json([
            "summary" => [
                "views_count" => $post->views_count,
                "unique_visitors" => (clone $views_query)
                    ->distinct()
                    ->count("visitor_hash"),
                "first_viewed_at" => (clone $views_query)->min("viewed_at"),
                "last_viewed_at" => (clone $views_query)->max("viewed_at"),
            ],
            "current_page" => $paginator->currentPage(),
            "last_page" => $paginator->lastPage(),
            "per_page" => $paginator->perPage(),
            "total" => $paginator->total(),
            "views" => $paginator->items(),
        ], 200);
    }

    public function store(Request $request, Post $post, RecordPostView $recordPostView)
    {
        $recordPostView($post, $request);
        return response()->noContent();
    }
}
