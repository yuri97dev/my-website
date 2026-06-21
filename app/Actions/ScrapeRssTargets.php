<?php

namespace App\Actions;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\ScrapeTarget;
use App\Models\ScrapeItem;
use App\Helpers\HtmlFormatter;
use Exception;

class ScrapeRssTargets
{
    public function __invoke(): void
    {
        $targets = ScrapeTarget::driver("rss")
            ->enabled()
            ->get();
        
        if( $targets->isEmpty() ) {
            $is_production = app()->isProduction();

            if( !$is_production ) {
                Log::info("ScrapeRssTargets: no targets to process");
            }

            return;
        }

        foreach($targets as $target) {
            try {
                $this->scrape($target);
            } catch (Exception $e) {
                Log::error("ScrapeRssTargets: failed to scrape target {$target->name}: {$e->getMessage()}");
            }
        }
    }

    private function scrape(ScrapeTarget $target): void
    {
        $response = Http::{$target->http_method}($target->list_url)->throw();

        $xml = simplexml_load_string($response->body());

        if( $xml === false ) {
            throw new Exception("Invalid XML.");
        }

        $data = json_decode(json_encode($xml), true);

        $list = Arr::get($data, $target->list_item_selector);

        if( empty($list) || !is_array($list) ) {
            throw new Exception("List selector not found.");
        }

        $items = collect($list)
            ->map(fn(array $item) => (object) [
                "title" => Arr::get($item, $target->item_title_selector),
                "link" => Arr::get($item, $target->item_link_selector),
            ])
            ->filter(fn($item) => !!$item->title && !!$item->link)
            ->take(10);

        foreach($items as $item) {
            $this->scrapeItem($target, $item);
            sleep(rand(1, 5));
        }
    }

    private function scrapeItem(ScrapeTarget $target, object $item): void
    {
        $already_scraped = ScrapeItem::query()
            ->where("scrape_target_id", $target->id)
            ->where("url", $item->link)
            ->exists();

        if( $already_scraped ) {
            return;
        }

        $response = Http::{$target->http_method}($item->link)->throw();

        $content = $response->body();

        $crawler = new Crawler($content);

        $main_content = $crawler
            ->filter($target->content_selector)
            ->html("body");

        $formatted_content = HtmlFormatter::all($main_content);

        ScrapeItem::create([
            "scrape_target_id" => $target->id,
            "url" => $item->link,
            "title" => $item->title,
            "content" => $formatted_content,
        ]);
    }
}
