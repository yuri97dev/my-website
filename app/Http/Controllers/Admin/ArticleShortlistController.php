<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ShortlistScrapedArticlesWithLaya;
use App\Http\Controllers\Controller;
use App\Models\ScrapedArticle;
use Illuminate\Http\JsonResponse;
use Throwable;

class ArticleShortlistController extends Controller
{
    public function __invoke(ShortlistScrapedArticlesWithLaya $shortlist): JsonResponse
    {
        try {
            $articles = $shortlist->recent();

            return response()->json([
                "data" => $articles->map(fn (ScrapedArticle $article) => [
                    "id" => $article->id,
                    "title" => $article->title,
                    "url" => $article->url,
                    "excerpt" => mb_substr(\App\Helpers\TextFormatter::removeHTML($article->content), 0, 500),
                ])->values(),
                "count" => $articles->count(),
            ]);
        } catch(Throwable $e) {
            report($e);
            return response()->json([
                "message" => "Não foi possível obter a pré-seleção do Laya.",
            ], 502);
        }
    }
}
