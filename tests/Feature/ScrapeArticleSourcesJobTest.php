<?php

use App\Jobs\ScrapeArticleSourcesJob;
use App\Models\ArticleSource;
use Illuminate\Support\Facades\Cache;

it("uses per-driver lock keys", function () {
    expect(ScrapeArticleSourcesJob::cacheLockKey(ArticleSource::DRIVER_RSS))
        ->toBe("scrape-driver-rss")
        ->and(ScrapeArticleSourcesJob::cacheLockKey(ArticleSource::DRIVER_BROWSER))
        ->toBe("scrape-driver-browser")
        ->and(ScrapeArticleSourcesJob::cacheLockKey(ArticleSource::DRIVER_JSON))
        ->toBe("scrape-driver-json")
        ->and(ScrapeArticleSourcesJob::cacheLockKey())
        ->toBe("scrape-all")
        ->and(ScrapeArticleSourcesJob::cacheLockKey(article_source_id: 42))
        ->toBe("scrape-source-42");
});

it("reports locked when lock is held", function () {
    $lock = Cache::lock(
        ScrapeArticleSourcesJob::cacheLockKey(ArticleSource::DRIVER_RSS),
        600,
    );

    $lock->get();

    expect(ScrapeArticleSourcesJob::isLocked(driver: ArticleSource::DRIVER_RSS))->toBeTrue();

    $lock->release();

    expect(ScrapeArticleSourcesJob::isLocked(driver: ArticleSource::DRIVER_RSS))->toBeFalse();
});

it("allows concurrent locks for different drivers", function () {
    $rss_lock = Cache::lock(
        ScrapeArticleSourcesJob::cacheLockKey(ArticleSource::DRIVER_RSS),
        600,
    );

    $rss_lock->get();

    expect(ScrapeArticleSourcesJob::isLocked(driver: ArticleSource::DRIVER_RSS))->toBeTrue()
        ->and(ScrapeArticleSourcesJob::isLocked(driver: ArticleSource::DRIVER_BROWSER))->toBeFalse();

    $rss_lock->release();
});

it("releases lock when job fails after acquiring it", function () {
    $job = new ScrapeArticleSourcesJob(driver: ArticleSource::DRIVER_RSS);

    $lock = Cache::lock(
        ScrapeArticleSourcesJob::cacheLockKey(ArticleSource::DRIVER_RSS),
        600,
    );

    $lock->get();

    $reflection = new ReflectionClass($job);
    $property = $reflection->getProperty("lock");
    $property->setAccessible(true);
    $property->setValue($job, $lock);

    expect(ScrapeArticleSourcesJob::isLocked(driver: ArticleSource::DRIVER_RSS))->toBeTrue();

    $job->failed(new RuntimeException("test failure"));

    expect(ScrapeArticleSourcesJob::isLocked(driver: ArticleSource::DRIVER_RSS))->toBeFalse();
});
