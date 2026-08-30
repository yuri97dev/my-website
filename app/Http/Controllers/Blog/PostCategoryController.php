<?php

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Blog\StorePostCategoryRequest;
use App\Http\Requests\Blog\UpdatePostCategoryRequest;
use App\Models\PostCategory;
use Illuminate\Http\Request;

class PostCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = PostCategory::query()
            ->select(["id", "slug", "name"]);

        if( $request->has("posts_count") ) {
            $query
                ->withCount([
                    "posts" => fn ($q) => $q->published(),
                ])
                ->orderByDesc("posts_count");
        } else {
            $query->orderBy("name");
        }

        return response()->json([
            "categories" => $query->get(),
        ], 200);
    }

    public function store(StorePostCategoryRequest $request)
    {
        $category = PostCategory::create($request->validated());

        return response()->json([
            "category" => $category->only(["id", "slug", "name"]),
        ], 201);
    }

    public function update(UpdatePostCategoryRequest $request, PostCategory $post_category)
    {
        $post_category->update($request->validated());

        return response()->json([
            "category" => $post_category->fresh()->only(["id", "slug", "name"]),
        ], 200);
    }

    public function destroy(PostCategory $post_category)
    {
        $post_category->delete();

        return response()->json([
            "message" => "Categoria removida com sucesso.",
        ], 200);
    }
}
