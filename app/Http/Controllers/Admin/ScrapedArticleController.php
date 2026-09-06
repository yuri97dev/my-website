<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\SearchQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\ScrapedArticle\BulkDeleteScrapedArticlesRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use App\Actions\TranslateScrapedArticle;
use App\Jobs\ScrapeArticleSourcesJob;
use App\Jobs\TranslateScrapedArticleJob;
use App\Models\ScrapedArticle;
use App\Models\User;

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
            "status_filters" => ["nullable", "array"],
            "status_filters.*" => ["string", Rule::in([
                "active",
                "inactive",
                "published",
                "unpublished",
                "translated",
                "untranslated",
                "rejected",
                "not_rejected",
                "assistant_processing",
                "assistant_processed",
                "not_assistant_processed",
            ])],
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
                "processing_by",
                "processing_at",
                "created_at",
                "updated_at",
            ])
            ->with([
                "source:id,name",
                "post:id,author_id",
            ])
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

        $this->applyStatusFilters($query, $request->input("status_filters", []));

        $paginator = $query->paginate($per_page);

        return response()->json([
            "current_page" => $paginator->currentPage(),
            "last_page" => $paginator->lastPage(),
            "per_page" => $paginator->perPage(),
            "total" => $paginator->total(),
            "assistant_author_id" => (int) config("assistant.author_id"),
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
        $scraped_article->update([
            "is_enabled" => false,
            "processing_by" => null,
            "processing_at" => null,
        ]);

        return response()->json([
            "scraped_article" => $scraped_article->fresh(),
            "message" => "Artigo inativado com sucesso.",
        ], 200);
    }

    public function scrape(Request $request)
    {
        $article_source_id = $request->integer("article_source_id") ?: null;

        if( ScrapeArticleSourcesJob::isLocked($article_source_id) ) return response()->json([
            "message" => "Scraping já está em andamento.",
        ], 409);

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
            "processing_by" => null,
            "processing_at" => null,
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

    public function translate(Request $request, ScrapedArticle $scraped_article)
    {
        if( !$scraped_article->is_enabled ) return response()->json([
            "message" => "O artigo está desativado.",
        ], 422);

        if( $scraped_article->translation_rejected_at ) return response()->json([
            "message" => "A tradução deste artigo foi rejeitada permanentemente.",
        ], 422);

        if( $scraped_article->translated_content ) return response()->json([
            "message" => "O artigo já possui tradução.",
        ], 422);

        if( $scraped_article->translation_attempts >= TranslateScrapedArticle::MAX_TRANSLATION_ATTEMPTS ) return response()->json([
            "message" => "O número máximo de tentativas de tradução foi atingido.",
        ], 422);

        if( $scraped_article->isProcessedByAssistant() ) return response()->json([
            "message" => "Este artigo está reservado pelo ajudante automático.",
        ], 409);

        if(
            $scraped_article->processing_by !== null &&
            $scraped_article->processing_by !== $request->user()->id
        ) return response()->json([
            "message" => "Este artigo está sendo processado por outro usuário.",
        ], 409);

        $scraped_article->load("source.aiPrompts.aiProvider");

        if( $scraped_article->source->aiPrompts->isEmpty() ) return response()->json([
            "message" => "A fonte do artigo não possui prompts de tradução configurados.",
        ], 422);

        if( $scraped_article->source->aiPrompts->contains(fn ($prompt) => !$prompt->aiProvider?->is_enabled) ) return response()->json([
            "message" => "Um ou mais provedores de IA estão desativados.",
        ], 422);

        $lock_key = TranslateScrapedArticleJob::cacheLockKey($scraped_article->id);
        $lock_acquired = Cache::add($lock_key, true, TranslateScrapedArticleJob::CACHE_LOCK_TTL);

        if( !$lock_acquired ) return response()->json([
            "message" => "A tradução deste artigo já está em andamento.",
        ], 409);

        $scraped_article->update([
            "processing_by" => $request->user()->id,
            "processing_at" => now(),
        ]);

        TranslateScrapedArticleJob::dispatch($scraped_article->id)->afterResponse();

        return response()->json([
            "message" => "Tradução iniciada com sucesso.",
        ], 202);
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

    private function applyStatusFilters(Builder $query, array $status_filters): void
    {
        $active = in_array("active", $status_filters, true);
        $inactive = in_array("inactive", $status_filters, true);

        if( $active && !$inactive ) {
            $query->where("is_enabled", true);
        } elseif( $inactive && !$active ) {
            $query->where("is_enabled", false);
        }

        $published = in_array("published", $status_filters, true);
        $unpublished = in_array("unpublished", $status_filters, true);

        if( $published && !$unpublished ) {
            $query->whereNotNull("post_id");
        } elseif( $unpublished && !$published ) {
            $query->whereNull("post_id");
        }

        $translated = in_array("translated", $status_filters, true);
        $untranslated = in_array("untranslated", $status_filters, true);

        if( $translated && !$untranslated ) {
            $query->whereNotNull("translated_title");
        } elseif( $untranslated && !$translated ) {
            $query->whereNull("translated_title");
        }

        $rejected = in_array("rejected", $status_filters, true);
        $not_rejected = in_array("not_rejected", $status_filters, true);

        if( $rejected && !$not_rejected ) {
            $query->whereNotNull("translation_rejected_at");
        } elseif( $not_rejected && !$rejected ) {
            $query->whereNull("translation_rejected_at");
        }

        $assistant_processing = in_array("assistant_processing", $status_filters, true);
        $assistant_processed = in_array("assistant_processed", $status_filters, true);
        $not_assistant_processed = in_array("not_assistant_processed", $status_filters, true);
        $assistant_author_id = (int) config("assistant.author_id");

        if( $assistant_processing && !$assistant_processed && !$not_assistant_processed ) {
            $query->where("processing_by", User::ASSISTANT_ID);
        } elseif( $assistant_processed && !$assistant_processing && !$not_assistant_processed ) {
            $query->whereHas("post", fn (Builder $query) => $query->where("author_id", $assistant_author_id));
        } elseif( $not_assistant_processed && !$assistant_processing && !$assistant_processed ) {
            $query
                ->where(function (Builder $query) {
                    $query
                        ->whereNull("processing_by")
                        ->orWhere("processing_by", "!=", User::ASSISTANT_ID);
                })
                ->whereDoesntHave("post", fn (Builder $query) => $query->where("author_id", $assistant_author_id));
        } elseif( $assistant_processing && $assistant_processed && !$not_assistant_processed ) {
            $query->where(function (Builder $query) use ($assistant_author_id) {
                $query
                    ->where("processing_by", User::ASSISTANT_ID)
                    ->orWhereHas("post", fn (Builder $query) => $query->where("author_id", $assistant_author_id));
            });
        }
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
