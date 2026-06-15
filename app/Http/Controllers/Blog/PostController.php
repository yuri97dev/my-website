<?php

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Blog\StorePostRequest;
use App\Http\Requests\Blog\UpdatePostRequest;
use App\Models\Post;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $is_system_admin = (bool) $request->user()?->isSystemAdmin();

        $per_page = min($request->integer("per_page", 15), 100);

        $query = Post::query()
            ->select([
                "id",
                "author_id",
                "category_id",
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
            ->orderByDesc("id");

        if( $is_system_admin ) {
            $query->withTrashed();
        } else {
            $query->published();
        }

        if( $request->has("id") ) {
            $query->where("id", $request->integer("id"));
        }

        if( $request->has("slug") ) {
            $query->where("slug", $request->string("slug"));
        }

        if( $request->has("category_id") ) {
            $query->where("category_id", $request->integer("category_id"));
        }

        $paginator = $query->paginate($per_page);

        return response()->json([
            "current_page" => $paginator->currentPage(),
            "last_page" => $paginator->lastPage(),
            "per_page" => $paginator->perPage(),
            "total" => $paginator->total(),
            "posts" => $paginator->items(),
        ], 200);
    }

    public function store(StorePostRequest $request)
    {
        $validated = $request->validated();

        $tags = $validated["tags"] ?? [];

        unset($validated["tags"]);
        unset($validated["published_at"]);

        $validated["author_id"] = $request->user()->id;
        $validated["status"] ??= Post::STATUS_DRAFT;

        if( $validated["status"] === Post::STATUS_PUBLISHED ) {
            $validated["published_at"] = now();
        }

        $post = Post::create($validated);

        if( !empty($tags) ) {
            $post->tags()->sync($tags);
        }

        $post->load([
            "author:id,name",
            "category:id,slug,name",
            "tags:id,slug,name",
        ]);

        return response()->json([
            "post" => $post,
        ], 201);
    }

    public function showBySlug(Request $request, string $slug)
    {
        $post = Post::query()
            ->where("slug", $slug)
            ->firstOrFail();

        return $this->showPost($request, $post);
    }

    public function show(Request $request, Post $post)
    {
        return $this->showPost($request, $post);
    }

    private function showPost(Request $request, Post $post)
    {
        $is_system_admin = (bool) $request->user()?->isSystemAdmin();

        $is_published = $post->status === Post::STATUS_PUBLISHED
            && $post->published_at !== null
            && $post->published_at->isPast();

        if( !$is_system_admin && !$is_published ) {
            abort(404);
        }

        $post->load([
            "author:id,name",
            "category:id,slug,name",
            "tags:id,slug,name",
        ]);

        return response()->json([
            "post" => $post,
        ], 200);
    }

    public function update(UpdatePostRequest $request, Post $post)
    {
        $validated = $request->validated();

        $tags = $validated["tags"] ?? [];

        unset($validated["tags"]);
        unset($validated["published_at"]);
        unset($validated["author_id"]);

        $validated["status"] ??= Post::STATUS_DRAFT;

        if( $validated["status"] === Post::STATUS_PUBLISHED ) {
            $validated["published_at"] = now();
        }

        $post->update($validated);

        if( !empty($tags) ) {
            $post->tags()->sync($tags);
        }

        $post->load([
            "author:id,name",
            "category:id,slug,name",
            "tags:id,slug,name",
        ]);

        return response()->json([
            "post" => $post,
        ], 200);
    }

    public function destroy(Post $post)
    {
        $post->delete();

        return response()->json([
            "message" => "Post removido com sucesso.",
        ], 200);
    }
}
