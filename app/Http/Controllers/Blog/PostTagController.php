<?php

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Blog\StorePostTagRequest;
use App\Http\Requests\Blog\UpdatePostTagRequest;
use App\Models\PostTag;
use Illuminate\Http\Request;

class PostTagController extends Controller
{
    public function index(Request $request)
    {
        $tags = PostTag::query()
            ->select(["id", "slug", "name"])
            ->orderBy("name")
            ->get();

        return response()->json([
            "tags" => $tags,
        ], 200);
    }

    public function store(StorePostTagRequest $request)
    {
        $tag = PostTag::create($request->validated());

        return response()->json([
            "tag" => $tag->only(["id", "slug", "name"]),
        ], 201);
    }

    public function update(UpdatePostTagRequest $request, PostTag $postTag)
    {
        $postTag->update($request->validated());

        return response()->json([
            "tag" => $postTag->fresh()->only(["id", "slug", "name"]),
        ], 200);
    }

    public function destroy(PostTag $postTag)
    {
        $postTag->delete();

        return response()->json([
            "message" => "Tag removida com sucesso.",
        ], 200);
    }
}
