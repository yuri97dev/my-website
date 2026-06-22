<?php

namespace App\Actions;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\ArticleSource;
use App\Models\ScrapedArticle;
use App\Helpers\HtmlFormatter;
use Exception;

class ScrapeRssArticleSources
{
    public function __invoke(): void
    {
        $sources = ArticleSource::driver("rss")
            ->enabled()
            ->get();
        
        if( $sources->isEmpty() ) {
            return;
        }

        foreach($sources as $source) {
            try {
                $this->scrape($source);
            } catch (Exception $e) {
                Log::error("ScrapeRssArticleSources: failed to scrape source {$source->name}: {$e->getMessage()}");
            }
        }
    }

    private function scrape(ArticleSource $source): void
    {
        $response = Http::{$source->http_method}($source->list_url)->throw();

        $xml = simplexml_load_string($response->body());

        if( $xml === false ) {
            throw new Exception("Invalid XML.");
        }

        $data = json_decode(json_encode($xml), true);

        $list = Arr::get($data, $source->list_item_selector);

        if( empty($list) || !is_array($list) ) {
            throw new Exception("List selector not found.");
        }

        $items = collect($list)
            ->map(fn(array $item) => (object) [
                "title" => Arr::get($item, $source->item_title_selector),
                "link" => Arr::get($item, $source->item_link_selector),
            ])
            ->filter(fn($item) => !!$item->title && !!$item->link)
            ->take(10);

        foreach($items as $item) {
            $this->scrapeArticle($source, $item);
            sleep(rand(1, 5));
        }
    }

    private function scrapeArticle(ArticleSource $source, object $item): void
    {
        $already_scraped = ScrapedArticle::query()
            ->where("article_source_id", $source->id)
            ->where("url", $item->link)
            ->exists();

        if( $already_scraped ) {
            return;
        }

        $response = Http::{$source->http_method}($item->link)->throw();

        $content = $response->body();

        $crawler = new Crawler($content);

        $main_content = $crawler
            ->filter($source->content_selector)
            ->html("body");

        $source_url = parse_url($source->list_url);
        $source_url = $source_url["scheme"] . "://" . $source_url["host"];

        $formatted_content = HtmlFormatter::all($main_content, $source_url);

        ScrapedArticle::create([
            "article_source_id" => $source->id,
            "url" => $item->link,
            "title" => $item->title,
            "content" => $formatted_content,
        ]);
    }
}
