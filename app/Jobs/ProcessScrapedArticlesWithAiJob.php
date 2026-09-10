<?php

namespace App\Jobs;

use App\Actions\ProcessScrapedArticlesWithAi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessScrapedArticlesWithAiJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const CACHE_LOCK_TTL = 3600;

    public int $uniqueFor = 3600;

    private ?Lock $lock = null;

    public function __construct()
    {
        $this->onQueue("default");
    }

    public function uniqueId(): string
    {
        return self::cacheLockKey();
    }

    public static function cacheLockKey(): string
    {
        return "process-scraped-articles-with-ai";
    }

    public static function isLocked(): bool
    {
        $lock = Cache::lock(self::cacheLockKey(), 1);

        if( $lock->get() ) {
            $lock->release();
            return false;
        }

        return true;
    }

    public function handle(): void
    {
        $this->lock = Cache::lock(self::cacheLockKey(), self::CACHE_LOCK_TTL);

        if( !$this->lock->get() ) {
            Log::info("ProcessScrapedArticlesWithAiJob: skipped, lock already held.");
            return;
        }

        try {
            (new ProcessScrapedArticlesWithAi)();
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
