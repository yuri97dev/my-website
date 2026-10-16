<?php

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\Blog\StorePostRequest;
use App\Http\Requests\Blog\UpdatePostRequest;
use League\HTMLToMarkdown\HtmlConverter;
use League\HTMLToMarkdown\Converter\TableConverter;
use App\Models\Post;
use App\Models\PostChunk;
use App\Models\SearchEmbeddingCache;
use App\Models\ScrapedArticle;
use App\Actions\NotifyPostPublished;
use App\Helpers\Meta;
use App\Helpers\SearchQuery;
use App\Jobs\GenerateSearchEmbeddingJob;
use Inertia\Inertia;

class PostController extends Controller
{
    public function index(?string $category = null)
    {
        return Inertia::render("Blog/Index", compact("category"));
    }

    public function show(string $category, string $slug, Request $request)
    {
        $is_system_admin = (bool) $request->user()?->isSystemAdmin();

        $query = Post::query()
            ->where("slug", $slug)
            ->with([
                "author:id,name",
                "category:id,slug,name",
                "tags:id,slug,name",
            ]);

        if( $is_system_admin ) {
            $query->withTrashed();
        } else {
            $query->published();
        }

        $post = $query->firstOrFail();

        /* if( $post->category->slug !== $category ) {
            abort(404);
        } */

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
                "status",
                "excerpt",
                "published_at",
                "meta_description",
                "thumbnail_path",
                "views_count",
                "created_at",
                "updated_at",
            ])
            ->with([
                "author:id,name",
                "category:id,slug,name",
                "tags:id,slug,name",
            ]);

        if( $is_system_admin ) {
            if( $request->boolean("trashed") ) {
                $query->addSelect([
                    "deleted_at",
                ]);

                $query->withTrashed();
            }

            if( $request->has("id") ) {
                $query->where("id", $request->integer("id"));
            }
        } else {
            $query->published();
        }

        if( $request->has("status") ) {
            $query->where("status", $request->string("status"));
        }

        if( $request->has("slug") ) {
            $query->where("slug", $request->string("slug"));
        }

        if( $request->has("title") ) {
            $query->whereRaw("ts_rank(
                to_tsvector('portuguese', title),
                websearch_to_tsquery('portuguese', ?)
            ) >= 0.01", [
                $request->string("title"),
            ]);
        }

        if( $request->has("category_id") ) {
            $query->where("category_id", $request->integer("category_id"));
        }

        if( $request->input("category") ) {
            $category = DB::table("post_categories")
                ->where("slug", $request->string("category"))
                ->first();

            if( !!$category ) {
                $query->where("category_id", $category->id);
            } else {
                $matching_categories = DB::table("post_categories")
                    ->whereRaw("ts_rank(
                        to_tsvector('portuguese', name),
                        websearch_to_tsquery('portuguese', ?)
                    ) >= 0.01", [
                        $request->string("category"),
                    ])
                    ->get();

                if( $matching_categories->isNotEmpty() ) {
                    $query->whereIn("category_id", $matching_categories->pluck("id")->all());
                } else {
                    $query->whereNull("category_id");
                }
            }
        }

        $search = $request->string("search", "");
        $search = trim($search);

        if( !empty($search) ) {
            $search_language = SearchQuery::language($search);
            $embedding = $this->resolveSearchEmbedding($search);

            $post_chunk_q1 = PostChunk::query()
                ->select(["post_id"])
                ->selectRaw(
                    "word_similarity(?, content) AS score",
                    [$search]
                );

            $post_chunk_q2 = PostChunk::query()
                ->select(["post_id"])
                ->selectRaw("
                    ts_rank(
                        to_tsvector(?::regconfig, content),
                        websearch_to_tsquery(?::regconfig, ?)
                    ) AS score
                ", [$search_language, $search_language, $search]);

            $post_chunk_q1 = $this->makeRanking($post_chunk_q1);
            $post_chunk_q2 = $this->makeRanking($post_chunk_q2);

            $post_chunk_q1->unionAll($post_chunk_q2);

            if( !!$embedding ) {
                $post_chunk_q3 = PostChunk::query()
                    ->select(["post_id"])
                    ->selectRaw("
                        1 - (embedding <=> ?::vector) AS score
                    ", [$embedding]);

                $post_chunk_q1->unionAll($this->makeRanking($post_chunk_q3));
            }

            $rrf = DB::query()
                ->fromSub($post_chunk_q1, "results")
                ->select(["post_id"])
                ->selectRaw("SUM(rrf_score) AS score")
                ->groupBy("post_id");

            $query->joinSub($rrf, "rrf", "rrf.post_id", "=", "posts.id");

            $query->addSelect(["rrf.score"]);
            $query->orderByDesc("rrf.score");
        } else {
            $query->orderByDesc("published_at");
            $query->orderByDesc("updated_at");
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

        $relations = [
            "author:id,name",
            "category:id,slug,name",
            "tags:id,slug,name",
        ];

        if( $is_system_admin ) {
            $relations[] = "scrapedArticle:id,post_id";
        }

        $post->load($relations);

        return response()->json([
            "post" => $post,
        ], 200);
    }

    public function store(StorePostRequest $request, NotifyPostPublished $notify_post_published)
    {
        $validated = $request->validated();

        $tags = $validated["tags"] ?? [];
        $scraped_article_id = $validated["scraped_article_id"] ?? null;

        unset($validated["tags"]);
        unset($validated["published_at"]);
        unset($validated["scraped_article_id"]);

        $validated["author_id"] = $request->user()->id;
        $validated["status"] ??= Post::STATUS_DRAFT;

        $is_new_publication = $validated["status"] === Post::STATUS_PUBLISHED;

        if( $is_new_publication ) {
            $validated["published_at"] = now();
        }

        if( empty($validated["excerpt"]) ) {
            $validated["excerpt"] = $this->generateExcerpt($validated["content"]);
        }

        $post = Post::create($validated);

        if( !empty($tags) ) {
            $post->tags()->sync($tags);
        }

        if( $scraped_article_id ) {
            ScrapedArticle::query()
                ->where("id", $scraped_article_id)
                ->whereNull("post_id")
                ->update(["post_id" => $post->id]);
        }

        $post->load([
            "author:id,name",
            "category:id,slug,name",
            "tags:id,slug,name",
        ]);

        if( $is_new_publication ) {
            $notify_post_published($post);
        }

        return response()->json([
            "post" => $post,
        ], 201);
    }

    public function update(UpdatePostRequest $request, Post $post, NotifyPostPublished $notify_post_published)
    {
        $validated = $request->validated();

        $should_sync_tags = array_key_exists("tags", $validated);
        $tags = $validated["tags"] ?? [];

        unset($validated["tags"]);
        unset($validated["published_at"]);
        unset($validated["author_id"]);

        $validated["status"] ??= Post::STATUS_DRAFT;

        $is_new_publication = $validated["status"] === Post::STATUS_PUBLISHED
            && $post->published_at === null;

        if( $is_new_publication ) {
            $validated["published_at"] = now();
        }

        if( empty($validated["excerpt"]) ) {
            $validated["excerpt"] = $this->generateExcerpt($validated["content"]);
        }

        $post->update($validated);

        if( $should_sync_tags ) {
            $post->tags()->sync($tags);
        }

        $post->load([
            "author:id,name",
            "category:id,slug,name",
            "tags:id,slug,name",
        ]);

        if( $is_new_publication ) {
            $notify_post_published($post);
        }

        return response()->json([
            "post" => $post,
        ], 200);
    }

    public function destroy(Post $post)
    {
        $post->scrapedArticle()->update([
            "is_enabled" => false,
            "processing_by" => null,
            "processing_at" => null,
        ]);

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

    private function resolveSearchEmbedding(string $search): ?string
    {
        $normalized_query = SearchQuery::normalize($search);
        $embed_model = config("services.ollama.embed_model");

        $cached = SearchEmbeddingCache::query()
            ->where("normalized_query", $normalized_query)
            ->where("embedding_model", $embed_model)
            ->first();

        if( $cached ) {
            return $cached->embedding;
        }

        GenerateSearchEmbeddingJob::dispatch($search, $normalized_query);

        return null;
    }

    private function makeRanking(Builder $query)
    {
        $chunks = DB::query()
            ->fromSub($query, "chunks")
            ->select(["post_id", "score"])
            ->selectRaw("
                ROW_NUMBER() OVER (
                    PARTITION BY post_id
                    ORDER BY score DESC
                ) AS position
            ");

        $scores = DB::query()
            ->fromSub($chunks, "ranked")
            ->select(["post_id"])
            ->selectRaw("
                SUM(
                    score * CASE position
                        WHEN 1 THEN 1.0
                        WHEN 2 THEN 0.5
                        WHEN 3 THEN 0.25
                    END
                ) AS score
            ")
            ->where("position", "<=", 3)
            ->where("score", ">", 0)
            ->groupBy("post_id");

        $ranking = DB::query()
            ->fromSub($scores, "scores")
            ->select(["post_id"])
            ->selectRaw("
                ROW_NUMBER() OVER (
                    ORDER BY score DESC, post_id
                ) AS position
            ");

        return DB::query()
            ->fromSub($ranking, "ranking")
            ->select(["post_id"])
            ->selectRaw("1.0 / (60 + position) AS rrf_score")
            ->where("position", "<=", 100);
    }
}
