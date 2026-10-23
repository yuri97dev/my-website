<?php

namespace App\Jobs;

use App\Actions\ShortlistScrapedArticlesWithLaya;
use App\Helpers\TextFormatter;
use App\Models\ScrapedArticle;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;
use Throwable;

class GenerateArticleShortlistJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public const CACHE_KEY = "article-shortlist:state";
    public const TTL = 7200;

    public int $timeout = ShortlistScrapedArticlesWithLaya::EXECUTION_TIMEOUT;

    public int $tries = 1;
    public bool $failOnTimeout = true;

    public function __construct(public string $run_id)
    {
        $this->onConnection("laya")->onQueue("article-shortlists");
    }

    public function handle(ShortlistScrapedArticlesWithLaya $shortlist): void
    {
        if( (Cache::get(self::CACHE_KEY)["run_id"] ?? null) !== $this->run_id ) return;

        $this->update([
            "status" => "running",
            "expires_at" => now()->addSeconds($this->timeout + 30)->timestamp,
        ]);

        $articles = $shortlist->recent(function (int $processed, int $total) {
            $this->update(["processed" => $processed, "total" => $total]);
        });

        $this->update([
            "status" => "completed",
            "data" => $articles->map(fn (ScrapedArticle $article) => [
                "id" => $article->id,
                "title" => $article->title,
                "url" => $article->url,
                "excerpt" => mb_substr(TextFormatter::removeHTML($article->content), 0, 500),
            ])->values()->all(),
            "count" => $articles->count(),
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        $this->update([
            "status" => "failed",
            "message" => "A seleção falhou ou excedeu o limite de 120 segundos. Uma nova tentativa deve ser iniciada manualmente.",
        ]);
    }

    private function update(array $attributes): void
    {
        $state = Cache::get(self::CACHE_KEY);
        if( ($state["run_id"] ?? null) !== $this->run_id ) return;
        Cache::put(self::CACHE_KEY, array_replace($state, $attributes), self::TTL);
    }
}
