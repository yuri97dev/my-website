<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\SearchQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\ScrapedArticle\BulkDeleteScrapedArticlesRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Jobs\ScrapeArticleSourcesJob;
use App\Models\ScrapedArticle;

class ScrapedArticleController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            "article_source_id" => ["nullable", "integer"],
            "id" => ["nullable", "integer"],
            "title" => ["nullable", "string", "max:255"],
            "translated_title" => ["nullable", "string", "max:255"],
            "url" => ["nullable", "string", "max:255"],
            "search" => ["nullable", "string", "max:255"],
        ]);

        $per_page = min($request->integer("per_page", 15), 100);

        $query = ScrapedArticle::query()
            ->select([
                "id",
                "article_source_id",
                "post_id",
                "url",
                "title",
                "translated_title",
                "is_enabled",
                "created_at",
                "updated_at",
            ])
            ->with("source:id,name")
            ->orderByDesc("id");

        if( $request->integer("article_source_id") ) {
            $query->where("article_source_id", $request->integer("article_source_id"));
        }

        $query
            ->when($request->filled("id"), function ($query) use ($request) {
                $query->where("id", $request->integer("id"));
            })
            ->when($request->filled("title"), function ($query) use ($request) {
                SearchQuery::applySubstringSearch(
                    $query,
                    "title",
                    $request->input("title")
                );
            })
            ->when($request->filled("translated_title"), function ($query) use ($request) {
                SearchQuery::applySubstringSearch(
                    $query,
                    "translated_title",
                    $request->input("translated_title")
                );
            })
            ->when($request->filled("url"), function ($query) use ($request) {
                SearchQuery::applySubstringSearch(
                    $query,
                    "url",
                    $request->input("url")
                );
            })
            ->when($request->filled("search"), function ($query) use ($request) {
                SearchQuery::applyWordSearch(
                    $query,
                    "concat_ws(' ', title, translated_title, url)",
                    $request->input("search")
                );
            });

        $paginator = $query->paginate($per_page);

        return response()->json([
            "current_page" => $paginator->currentPage(),
            "last_page" => $paginator->lastPage(),
            "per_page" => $paginator->perPage(),
            "total" => $paginator->total(),
            "scraped_articles" => $paginator->items(),
        ], 200);
    }

    public function show(ScrapedArticle $scraped_article)
    {
        $scraped_article->load("source:id,name,driver");

        return response()->json([
            "scraped_article" => $scraped_article,
        ], 200);
    }

    public function disable(ScrapedArticle $scraped_article)
    {
        $scraped_article->update(["is_enabled" => false]);

        return response()->json([
            "scraped_article" => $scraped_article->fresh(),
            "message" => "Artigo inativado com sucesso.",
        ], 200);
    }

    public function scrape(Request $request)
    {
        $lock_acquired = Cache::add(
            ScrapeArticleSourcesJob::CACHE_LOCK_KEY,
            true,
            ScrapeArticleSourcesJob::CACHE_LOCK_TTL,
        );

        if( !$lock_acquired ) return response()->json([
            "message" => "Scraping já está em andamento.",
        ], 409);

        $article_source_id = $request->integer("article_source_id") ?: null;

        ScrapeArticleSourcesJob::dispatch($article_source_id)->afterResponse();

        return response()->json([
            "message" => "Scraping iniciado com sucesso.",
        ], 202);
    }

    public function rejectTranslation(Request $request, ScrapedArticle $scraped_article)
    {
        $permanent = $request->boolean("permanent");

        $data = [
            "translated_title" => null,
            "translated_content" => null,
        ];

        if( $permanent ) {
            $data["translation_rejected_at"] = now();
        }

        $scraped_article->update($data);
        $scraped_article->translationCaches()->delete();

        return response()->json([
            "scraped_article" => $scraped_article->fresh(),
            "message" => $permanent
                ? "Tradução rejeitada permanentemente."
                : "Tradução rejeitada com sucesso.",
        ], 200);
    }

    public function resetTranslationAttempts(ScrapedArticle $scraped_article)
    {
        $scraped_article->update(["translation_attempts" => 0]);

        return response()->json([
            "scraped_article" => $scraped_article->fresh(),
            "message" => "Contagem de tentativas de tradução reiniciada com sucesso.",
        ], 200);
    }

    public function bulkDeleteCount(BulkDeleteScrapedArticlesRequest $request)
    {
        $count = $this->buildBulkDeleteQuery($request)->count();

        return response()->json([
            "count" => $count,
        ], 200);
    }

    public function bulkDelete(BulkDeleteScrapedArticlesRequest $request)
    {
        $deleted_count = $this->buildBulkDeleteQuery($request)->delete();

        return response()->json([
            "deleted_count" => $deleted_count,
            "message" => "Artigos excluídos com sucesso.",
        ], 200);
    }

    private function buildBulkDeleteQuery(BulkDeleteScrapedArticlesRequest $request): Builder
    {
        $query = ScrapedArticle::query()
            ->whereNull("post_id");

        if( $request->integer("article_source_id") ) {
            $query->where("article_source_id", $request->integer("article_source_id"));
        }

        $date_from = $request->date("date_from");
        $date_to = $request->date("date_to");

        if( $date_from && $date_to ) {
            $query->whereDate("created_at", ">=", $date_from);
            $query->whereDate("created_at", "<=", $date_to);
        } elseif( $date_from ) {
            $query->whereDate("created_at", $date_from);
        }

        if( $request->boolean("untranslated_only") ) {
            $query->whereNull("translated_title");
        }

        return $query;
    }
}
