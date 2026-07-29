<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Actions\RemoveExpiredSpreadsheetFiles;
use App\Actions\GeneratePostChunks;
use App\Actions\ProcessScrapedArticlesWithAi;
use App\Jobs\ScrapeArticleSourcesJob;
use App\Models\ArticleSource;

Artisan::command("inspire", function () {
    $this->comment(Inspiring::quote());
})->purpose("Display an inspiring quote");

Schedule::call(new RemoveExpiredSpreadsheetFiles)
    ->name("remove-expired-spreadsheet-files")
    ->everyMinute()
    ->withoutOverlapping();

Schedule::call(new GeneratePostChunks)
    ->name("generate-post-chunks")
    ->everyTenSeconds()
    ->withoutOverlapping();

Schedule::job(new ScrapeArticleSourcesJob(driver: ArticleSource::DRIVER_RSS))
    ->name("scrape-rss-article-sources")
    ->everyTwoHours()
    ->unlessBetween("01:00", "06:00")
    ->withoutOverlapping();

Schedule::job(new ScrapeArticleSourcesJob(driver: ArticleSource::DRIVER_BROWSER))
    ->name("scrape-browser-article-sources")
    ->everyTwoHours()
    ->unlessBetween("01:00", "06:00")
    ->withoutOverlapping();

Schedule::job(new ScrapeArticleSourcesJob(driver: ArticleSource::DRIVER_JSON))
    ->name("scrape-json-article-sources")
    ->everyTwoHours()
    ->unlessBetween("01:00", "06:00")
    ->withoutOverlapping();

Schedule::call(new ProcessScrapedArticlesWithAi)
    ->name("process-scraped-articles-with-ai")
    ->everyMinute()
    ->withoutOverlapping();
