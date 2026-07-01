<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Actions\RemoveExpiredSpreadsheetFiles;
use App\Actions\GeneratePostChunks;
use App\Actions\TranslateScrapedArticles;
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
    ->withoutOverlapping();

Schedule::job(new ScrapeArticleSourcesJob(driver: ArticleSource::DRIVER_BROWSER))
    ->name("scrape-browser-article-sources")
    ->everyTwoHours()
    ->withoutOverlapping();

Schedule::call(new TranslateScrapedArticles)
    ->name("translate-scraped-articles")
    ->everyMinute()
    ->withoutOverlapping();
