<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ScrapedArticle;

class ScrapedArticleController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min($request->integer("per_page", 15), 100);

        $paginator = ScrapedArticle::query()
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
            ->orderByDesc("id")
            ->paginate($perPage);

        return response()->json([
            "current_page" => $paginator->currentPage(),
            "last_page" => $paginator->lastPage(),
            "per_page" => $paginator->perPage(),
            "total" => $paginator->total(),
            "scraped_articles" => $paginator->items(),
        ], 200);
    }

    public function show(ScrapedArticle $scrapedArticle)
    {
        $scrapedArticle->load("source:id,name,driver");

        return response()->json([
            "scraped_article" => $scrapedArticle,
        ], 200);
    }

    public function disable(ScrapedArticle $scrapedArticle)
    {
        $scrapedArticle->update(["is_enabled" => false]);

        return response()->json([
            "scraped_article" => $scrapedArticle->fresh(),
            "message" => "Artigo inativado com sucesso.",
        ], 200);
    }
}
