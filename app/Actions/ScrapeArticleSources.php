<?php

namespace App\Actions;

use App\Models\ArticleSource;
use Exception;

class ScrapeArticleSources
{
    public function __invoke(?ArticleSource $source = null, ?string $driver = null): void
    {
        if( $source ) {
            if( !$source->is_enabled ) {
                return;
            }

            $this->scrapeSource($source);
            return;
        }

        if( $driver === ArticleSource::DRIVER_RSS ) {
            (new ScrapeRssArticleSources)();
            return;
        }

        if( $driver === ArticleSource::DRIVER_BROWSER ) {
            (new ScrapeBrowserArticleSources)();
            return;
        }

        (new ScrapeRssArticleSources)();
        (new ScrapeBrowserArticleSources)();
    }

    private function scrapeSource(ArticleSource $source): void
    {
        match($source->driver) {
            ArticleSource::DRIVER_RSS => (new ScrapeRssArticleSources)->scrapeSource($source),
            ArticleSource::DRIVER_BROWSER => (new ScrapeBrowserArticleSources)->scrapeSource($source),
            default => throw new Exception("Invalid driver."),
        };
    }
}
