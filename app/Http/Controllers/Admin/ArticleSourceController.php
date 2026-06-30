<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\ArticleSource\StoreArticleSourceRequest;
use App\Http\Requests\ArticleSource\UpdateArticleSourceRequest;
use App\Http\Requests\ArticleSource\TestArticleSourceRequest;
use App\Models\ArticleSource;
use App\Actions\TestScrapeArticleSource;
use Exception;

class ArticleSourceController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min($request->integer("per_page", 15), 100);

        $paginator = ArticleSource::query()
            ->select([
                "id",
                "driver",
                "name",
                "locale",
                "http_method",
                "list_url",
                "list_item_selector",
                "item_title_selector",
                "item_link_selector",
                "content_selector",
                "is_enabled",
                "created_at",
                "updated_at",
            ])
            ->orderByDesc("id")
            ->paginate($perPage);

        return response()->json([
            "current_page" => $paginator->currentPage(),
            "last_page" => $paginator->lastPage(),
            "per_page" => $paginator->perPage(),
            "total" => $paginator->total(),
            "article_sources" => $paginator->items(),
        ], 200);
    }

    public function store(StoreArticleSourceRequest $request)
    {
        $articleSource = ArticleSource::create($request->validated());

        return response()->json([
            "article_source" => $articleSource,
        ], 201);
    }

    public function update(UpdateArticleSourceRequest $request, ArticleSource $articleSource)
    {
        $articleSource->update($request->validated());

        return response()->json([
            "article_source" => $articleSource->fresh(),
        ], 200);
    }

    public function disable(ArticleSource $articleSource)
    {
        $articleSource->update(["is_enabled" => false]);

        return response()->json([
            "article_source" => $articleSource->fresh(),
            "message" => "Fonte desabilitada com sucesso.",
        ], 200);
    }

    public function test(TestArticleSourceRequest $request, TestScrapeArticleSource $action)
    {
        try {
            $result = $action($request->validated());
            return response()->json($result, 200);
        } catch (Exception $e) {
            return response()->json([
                "message" => $e->getMessage(),
            ], 422);
        }
    }
}
