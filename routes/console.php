<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Actions\RemoveExpiredSpreadsheetFiles;
use App\Actions\GeneratePostChunks;
use App\Actions\ScrapeRssArticleSources;
use App\Actions\ScrapeBrowserArticleSources;

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

Schedule::call(new ScrapeRssArticleSources)
    ->name("scrape-rss-article-sources")
    ->everyTwoHours()
    ->withoutOverlapping();

Schedule::call(new ScrapeBrowserArticleSources)
    ->name("scrape-browser-article-sources")
    ->everyTwoHours()
    ->withoutOverlapping();
