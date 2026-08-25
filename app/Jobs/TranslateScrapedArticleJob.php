<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use App\Actions\TranslateScrapedArticle;
use App\Models\ScrapedArticle;

class TranslateScrapedArticleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const CACHE_LOCK_TTL = 900;

    public function __construct(
        protected int $scraped_article_id,
    ) {
        $this->onQueue("scraped-article-translations");
    }

    public static function cacheLockKey(int $scraped_article_id): string
    {
        return "translate-scraped-article-{$scraped_article_id}";
    }

    public function handle(): void
    {
        $lock_key = self::cacheLockKey($this->scraped_article_id);

        $lock_acquired_by_controller = Cache::has($lock_key);
        $lock_acquired = Cache::add($lock_key, true, self::CACHE_LOCK_TTL);

        if( !$lock_acquired_by_controller && !$lock_acquired ) return;

        try {
            $article = ScrapedArticle::query()
                ->enabled()
                ->whereNull("translation_rejected_at")
                ->whereNull("translated_content")
                ->where("translation_attempts", "<", TranslateScrapedArticle::MAX_TRANSLATION_ATTEMPTS)
                ->whereHas("source.aiPrompts")
                ->whereDoesntHave("source.aiPrompts.aiProvider", fn ($query) => $query->where("is_enabled", false))
                ->find($this->scraped_article_id);

            if( !$article ) return;

            (new TranslateScrapedArticle)($article);
        } finally {
            Cache::forget($lock_key);
        }
    }
}
