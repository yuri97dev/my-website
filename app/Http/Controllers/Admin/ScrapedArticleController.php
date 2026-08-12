<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Jobs\ScrapeArticleSourcesJob;
use App\Models\ScrapedArticle;

class ScrapedArticleController extends Controller
{
    public function index(Request $request)
    {
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
}
