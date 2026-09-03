<?php

use App\Actions\GeneratePostChunks;
use App\Actions\GenerateSitemap;
use App\Actions\OptimizePendingImages;
use App\Actions\ProcessScrapedArticlesWithAi;
use App\Actions\RemoveExpiredSpreadsheetFiles;
use App\Jobs\ScrapeArticleSourcesJob;
use App\Models\ArticleSource;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command("inspire", function () {
    $this->comment(Inspiring::quote());
})->purpose("Display an inspiring quote");

Artisan::command("sitemap:generate", function () {
    (new GenerateSitemap)();
    $this->info("Sitemap gerado em public/sitemap.xml");
})->purpose("Gera o arquivo estático do sitemap");

Artisan::command("images:optimize {--limit=50 : Quantidade máxima de imagens por execução} {--sync : Processa de forma síncrona em vez de enfileirar}", function () {
    $limit = max(1, (int) $this->option("limit"));
    $sync = (bool) $this->option("sync");

    $dispatched = (new OptimizePendingImages)($limit, $sync);

    $mode = $sync ? "processadas" : "enfileiradas";

    $this->info("{$dispatched} imagem(ns) {$mode}.");
})->purpose("Otimiza imagens pendentes convertendo-as para WebP");

Schedule::call(new GenerateSitemap)
    ->name("generate-sitemap")
    ->dailyAt("03:00")
    ->withoutOverlapping(15);

Schedule::call(new RemoveExpiredSpreadsheetFiles)
    ->name("remove-expired-spreadsheet-files")
    ->everyMinute()
    ->withoutOverlapping(15);

Schedule::call(new GeneratePostChunks)
    ->name("generate-post-chunks")
    ->everyTenSeconds()
    ->withoutOverlapping(15);

Schedule::call(new OptimizePendingImages)
    ->name("optimize-pending-images")
    ->everyFiveMinutes()
    ->withoutOverlapping(15);

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

/* Schedule::call(new ProcessScrapedArticlesWithAi)
    ->name("process-scraped-articles-with-ai")
    ->everyMinute()
    ->withoutOverlapping(15); */
