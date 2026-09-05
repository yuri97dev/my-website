<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Actions\ScrapeArticleSources;
use App\Models\ArticleSource;
use Throwable;

class ScrapeArticleSourcesJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const CACHE_LOCK_TTL = 600;

    public int $uniqueFor = 600;

    private ?Lock $lock = null;

    public function __construct(
        protected ?int $article_source_id = null,
        protected ?string $driver = null,
    ) {
        $this->onQueue("scraping");
    }

    public function uniqueId(): string
    {
        return self::cacheLockKey($this->driver, $this->article_source_id);
    }

    public static function cacheLockKey(?string $driver = null, ?int $article_source_id = null): string
    {
        if( $article_source_id ) {
            return "scrape-source-{$article_source_id}";
        }

        if( $driver ) {
            return "scrape-driver-{$driver}";
        }

        return "scrape-all";
    }

    public static function isLocked(?int $article_source_id = null, ?string $driver = null): bool
    {
        $lock = Cache::lock(
            self::cacheLockKey($driver, $article_source_id),
            1,
        );

        if( $lock->get() ) {
            $lock->release();

            return false;
        }

        return true;
    }

    public function handle(): void
    {
        $this->lock = Cache::lock(
            self::cacheLockKey($this->driver, $this->article_source_id),
            self::CACHE_LOCK_TTL,
        );

        if( !$this->lock->get() ) {
            Log::info("ScrapeArticleSourcesJob: skipped, lock already held.", [
                "driver" => $this->driver,
                "article_source_id" => $this->article_source_id,
            ]);

            return;
        }

        try {
            $source = $this->article_source_id
                ? ArticleSource::find($this->article_source_id)
                : null;

            if( $this->article_source_id && !$source ) {
                return;
            }

            (new ScrapeArticleSources)($source, $this->driver);
        } finally {
            $this->releaseLock();
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->releaseLock();
    }

    private function releaseLock(): void
    {
        if( !$this->lock ) {
            return;
        }

        $this->lock->release();
        $this->lock = null;
    }
}
