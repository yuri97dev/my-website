<?php

namespace App\Actions;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\ArticleSource;
use App\Models\ScrapedArticle;
use App\Actions\DownloadScrapedArticleImages;
use App\Helpers\HtmlFormatter;
use Exception;

class ScrapeJsonArticleSources
{
    public function __invoke(): void
    {
        $sources = ArticleSource::driver("json")
            ->enabled()
            ->get();

        if( $sources->isEmpty() ) {
            return;
        }

        foreach($sources as $source) {
            try {
                $this->scrape($source);
            } catch (Exception $e) {
                Log::error("ScrapeJsonArticleSources: failed to scrape source {$source->name}: {$e->getMessage()}");
            }
        }
    }

    public function scrapeSource(ArticleSource $source): void
    {
        $this->scrape($source);
    }

    public function scrapeOne(ArticleSource $source): array
    {
        $item = $this->fetchItems($source)->first();

        if( !$item ) {
            throw new Exception("List selector not found.");
        }

        return $this->buildArticle($source, $item);
    }

    private function scrape(ArticleSource $source): void
    {
        foreach($this->fetchItems($source)->take(10) as $item) {
            $this->persistArticle($source, $item);
            sleep(rand(1, 5));
        }
    }

    private function fetchItems(ArticleSource $source): Collection
    {
        $response = Http::{$source->http_method}($source->list_url)->throw();

        $data = $response->json();

        if( !is_array($data) ) {
            throw new Exception("Invalid JSON.");
        }

        $list = Arr::get($data, $source->list_item_selector);

        if( empty($list) || !is_array($list) ) {
            throw new Exception("List selector not found.");
        }

        return collect($list)
            ->map(fn(array $item) => (object) [
                "title" => Arr::get($item, $source->item_title_selector),
                "link" => $source->resolveItemLink(Arr::get($item, $source->item_link_selector)),
            ])
            ->filter(fn($item) => !!$item->title && !!$item->link);
    }

    private function buildArticle(ArticleSource $source, object $item): array
    {
        $response = Http::{$source->http_method}($item->link)->throw();
        $body = $response->body();

        $data = json_decode($body, true);

        if( json_last_error() === JSON_ERROR_NONE && is_array($data) ) {
            $content = Arr::get($data, $source->content_selector);

            if( empty($content) ) {
                throw new Exception("Content selector not found.");
            }

            if( is_array($content) ) {
                $content = json_encode($content, JSON_UNESCAPED_UNICODE);
            }

            return [
                "title" => $item->title,
                "url" => $item->link,
                "content" => (string) $content,
            ];
        }

        $crawler = new Crawler($body);

        $main_content = $crawler
            ->filter($source->content_selector)
            ->html("body");

        $source_url = parse_url($source->list_url);
        $source_url = $source_url["scheme"] . "://" . $source_url["host"];

        return [
            "title" => $item->title,
            "url" => $item->link,
            "content" => HtmlFormatter::all($main_content, $source_url),
        ];
    }

    private function persistArticle(ArticleSource $source, object $item): void
    {
        $already_scraped = ScrapedArticle::query()
            ->where("article_source_id", $source->id)
            ->where("url", $item->link)
            ->exists();

        if( $already_scraped ) {
            return;
        }

        $article = ScrapedArticle::create([
            "article_source_id" => $source->id,
            ...$this->buildArticle($source, $item),
        ]);

        (new DownloadScrapedArticleImages)($article);
    }
}
