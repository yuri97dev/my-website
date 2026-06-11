<?php

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min($request->integer("per_page", 15), 100);

        $query = Post::query()
            ->published()
            ->select([
                "id",
                "author_id",
                "post_category_id",
                "slug",
                "title",
                "excerpt",
                "published_at",
                "meta_description",
                "thumbnail_path",
                "created_at",
                "updated_at",
            ])
            ->with([
                "author:id,name",
                "category:id,slug,name",
                "tags:id,slug,name",
            ])
            ->orderByDesc("published_at");
        
        if( $request->has("id") ) {
            $query->where("id", $request->integer("id"));
        }

        if( $request->has("slug") ) {
            $query->where("slug", $request->string("slug"));
        }

        if( $request->has("category") ) {
            $query->where("post_category_id", $request->integer("category"));
        }

        $paginator = $query->paginate($perPage);

        return response()->json([
            "current_page" => $paginator->currentPage(),
            "last_page" => $paginator->lastPage(),
            "per_page" => $paginator->perPage(),
            "total" => $paginator->total(),
            "posts" => $paginator->items(),
        ], 200);
    }
}
