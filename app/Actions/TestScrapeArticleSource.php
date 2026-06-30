<?php

namespace App\Actions;

use App\Models\ArticleSource;
use Exception;

class TestScrapeArticleSource
{
    public function __invoke(array $data): array
    {
        $source = new ArticleSource($data);

        return match($source->driver) {
            ArticleSource::DRIVER_RSS => (new ScrapeRssArticleSources)->scrapeOne($source),
            ArticleSource::DRIVER_BROWSER => (new ScrapeBrowserArticleSources)->scrapeOne($source),
            default => throw new Exception("Invalid driver."),
        };
    }
}
