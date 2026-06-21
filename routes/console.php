<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Actions\RemoveExpiredSpreadsheetFiles;
use App\Actions\GeneratePostChunks;
use App\Actions\ScrapeRssTargets;
use App\Actions\ScrapeBrowserTargets;

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

Schedule::call(new ScrapeRssTargets)
    ->name("scrape-rss-targets")
    ->everyTwoHours()
    ->withoutOverlapping();

Schedule::call(new ScrapeBrowserTargets)
    ->name("scrape-browser-targets")
    ->everyTwoHours()
    ->withoutOverlapping();
