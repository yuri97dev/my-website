<?php

namespace App\Actions;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Panther\Client;
use Facebook\WebDriver\Remote\RemoteWebElement;
use App\Models\ArticleSource;
use App\Models\ScrapedArticle;
use App\Actions\DownloadScrapedArticleImages;
use App\Helpers\HtmlFormatter;
use Exception;

class ScrapeBrowserArticleSources
{
    public function __invoke(): void
    {
        $sources = ArticleSource::driver("browser")
            ->enabled()
            ->get();

        if( $sources->isEmpty() ) {
            return;
        }

        foreach($sources as $source) {
            $client = $this->createClient($source);

            try {
                $this->scrape($client, $source);
            } catch (Exception $e) {
                Log::error("ScrapeBrowserArticleSources: failed to scrape source {$source->name}: {$e->getMessage()}");
            } finally {
                $client->quit();
            }
        }
    }

    public function scrapeSource(ArticleSource $source): void
    {
        $client = $this->createClient($source);

        try {
            $this->scrape($client, $source);
        } finally {
            $client->quit();
        }
    }

    public function scrapeOne(ArticleSource $source): array
    {
        $client = $this->createClient($source);

        try {
            $item = $this->fetchItems($client, $source)->first();

            if( !$item ) {
                throw new Exception("List selector not found.");
            }

            return $this->buildArticle($client, $source, $item);
        } finally {
            $client->quit();
        }
    }

    private function scrape(Client $client, ArticleSource $source): void
    {
        $items = $this->fetchItems($client, $source)->take(10);

        if( $items->isEmpty() ) {
            throw new Exception("List selector not found.");
        }

        foreach($items as $item) {
            $this->persistArticle($client, $source, $item);
            sleep(rand(1, 5));
        }
    }

    private function createClient(ArticleSource $source): Client
    {
        $arguments = [
            "--headless=new",
            "--no-sandbox",
            "--disable-dev-shm-usage",
            "--disable-gpu",
            "--user-data-dir=/tmp/chrome-profile-{$source->id}",
            "--crash-dumps-dir=/tmp/chrome-crashes",
            "--window-size=1200,1100",
        ];

        if( $source->user_agent ) {
            $arguments[] = "--user-agent={$source->user_agent}";
        }

        if( $source->proxy_server ) {
            $arguments[] = "--proxy-server={$source->proxy_server}";
        }

        if( $source->accept_language ) {
            $lang = trim(explode(",", $source->accept_language)[0]);
            $arguments[] = "--lang={$lang}";
        }

        return Client::createChromeClient(null, $arguments);
    }

    private function fetchItems(Client $client, ArticleSource $source): Collection
    {
        $client->request($source->http_method, $source->list_url);

        $client->waitFor($source->list_item_selector);

        $list = $client->getCrawler()->filter($source->list_item_selector);

        return collect($list->each(function($node) use ($source) {
            $title = $node->filter($source->item_title_selector)->text();
            $link = $node->filter($source->item_link_selector)->attr("href");

            return (object) [
                "title" => $title,
                "link" => $source->resolveItemLink($link),
            ];
        }))->filter(fn($item) => !!$item->title && !!$item->link);
    }

    private function buildArticle(Client $client, ArticleSource $source, object $item): array
    {
        $client->get($item->link);

        $client->waitFor($source->content_selector);

        /** @var RemoteWebElement $element */
        $element = $client->getCrawler()
            ->filter($source->content_selector)
            ->getElement(0);

        $content = $element->getDomProperty("innerHTML");

        $source_url = parse_url($source->list_url);
        $source_url = $source_url["scheme"] . "://" . $source_url["host"];

        return [
            "title" => $item->title,
            "url" => $item->link,
            "content" => HtmlFormatter::all($content, $source_url),
        ];
    }

    private function persistArticle(Client $client, ArticleSource $source, object $item): void
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
            ...$this->buildArticle($client, $source, $item),
        ]);

        (new DownloadScrapedArticleImages)($article);
    }
}
