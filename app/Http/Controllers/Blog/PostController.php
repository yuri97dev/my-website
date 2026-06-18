<?php

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Blog\StorePostRequest;
use App\Http\Requests\Blog\UpdatePostRequest;
use League\HTMLToMarkdown\HtmlConverter;
use League\HTMLToMarkdown\Converter\TableConverter;
use App\Models\Post;
use App\Models\PostChunk;
use App\Helpers\Meta;
use App\Helpers\Ollama;
use Inertia\Inertia;
use Exception;

class PostController extends Controller
{
    public function index(?string $category = null)
    {
        return Inertia::render("Blog/Index", compact("category"));
    }

    public function show(string $category, string $slug)
    {
        $post = Post::query()
            ->where("slug", $slug)
            ->with([
                "author:id,name",
                "category:id,slug,name",
                "tags:id,slug,name",
            ])
            ->firstOrFail();

        if( $post->category->slug !== $category ) {
            abort(404);
        }

        Meta::set([
            "title" => $post->title,
            "description" => $post->excerpt,
            "og_title" => $post->title,
            "og_description" => $post->excerpt,
            "og_image" => $post->thumbnail_path,
        ]);

        return Inertia::render("Blog/Show", compact("post"));
    }

    public function list(Request $request)
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
            ]);

        if( $is_system_admin ) {
            $query->withTrashed();

            if( $request->has("id") ) {
                $query->where("id", $request->integer("id"));
            }
        } else {
            $query->published();
        }

        if( $request->has("slug") ) {
            $query->where("slug", $request->string("slug"));
        }

        if( $request->has("category_id") ) {
            $query->where("category_id", $request->integer("category_id"));
        }

        if( $request->input("category") ) {
            $category = DB::table("post_categories")
                ->where("slug", $request->string("category"))
                ->first();

            $category_id = $category->id ?? -1;

            $query->where("category_id", $category_id);
        }

        $search = $request->string("search", "");
        $search = trim($search);

        if( !empty($search) ) {
            $embedding = null;

            try {
                $ollama_response = Ollama::embed($search);

                if( !empty($ollama_response["embeddings"]) ) {
                    $embedding = json_encode($ollama_response["embeddings"][0]);
                }
            } catch(Exception $e) {
                report($e);
            }

            $query_lexical_similarity = "ts_rank(
                to_tsvector('portuguese', content),
                websearch_to_tsquery('portuguese', ?)
            )";

            $query_semantic_similarity = !!$embedding
                ? "(1 - (embedding <=> ?::vector))"
                : "0";

            $query_chunks = PostChunk::query()
                ->select([
                    "post_id",
                    DB::raw("MAX(
                        ({$query_lexical_similarity} * 0.3) +
                        ({$query_semantic_similarity} * 0.7)
                    ) AS similarity_score"),
                ])
                ->groupBy("post_id")
                ->addBinding($search, "select");

            if( !!$embedding ) {
                $query_chunks->addBinding($embedding, "select");
            }

            $query->joinSub($query_chunks, "chunks", "chunks.post_id", "posts.id");

            if( !!$embedding ) {
                $query->whereRaw("chunks.similarity_score > 0.2");
            } else {
                $query->whereRaw("chunks.similarity_score > 0.01");
            }

            $query->addSelect([
                "chunks.similarity_score",
            ]);

            $query->orderByDesc("similarity_score");
        } else {
            $query->orderByDesc("id");
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

    public function getById(Request $request, Post $post)
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

        if( empty($validated["excerpt"]) ) {
            $validated["excerpt"] = $this->generateExcerpt($validated["content"]);
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

        if( empty($validated["excerpt"]) ) {
            $validated["excerpt"] = $this->generateExcerpt($validated["content"]);
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

    private function generateExcerpt(string $content): string
    {
        $converter = new HtmlConverter([ "strip_tags" => true ]);
        $converter->getEnvironment()->addConverter(new TableConverter());

        $excerpt = $converter->convert($content);

        return mb_substr($excerpt, 0, 160) . "...";
    }
}
