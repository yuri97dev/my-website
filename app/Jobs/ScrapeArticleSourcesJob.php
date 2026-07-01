<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use App\Actions\ScrapeArticleSources;
use App\Models\ArticleSource;

class ScrapeArticleSourcesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const CACHE_LOCK_KEY = "scraping-active";
    public const CACHE_LOCK_TTL = 600;

    public function __construct(
        protected ?int $articleSourceId = null,
        protected ?string $driver = null,
    ) {
        $this->onQueue("scraping");
    }

    public function handle(): void
    {
        $lockAcquiredByController = Cache::has(self::CACHE_LOCK_KEY);
        $lockAcquired = Cache::add(self::CACHE_LOCK_KEY, true, self::CACHE_LOCK_TTL);

        if( !$lockAcquiredByController && !$lockAcquired ) return;

        try {
            $source = $this->articleSourceId
                ? ArticleSource::find($this->articleSourceId)
                : null;

            if( $this->articleSourceId && !$source ) return;

            (new ScrapeArticleSources)($source, $this->driver);
        } finally {
            Cache::forget(self::CACHE_LOCK_KEY);
        }
    }
}
