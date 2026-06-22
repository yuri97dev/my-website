<?php

namespace App\Actions;

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

        $client = Client::createChromeClient(null, [
            "--headless=new",
            "--no-sandbox",
            "--disable-dev-shm-usage",
            "--disable-gpu",
            "--user-data-dir=/tmp/chrome-profile",
            "--crash-dumps-dir=/tmp/chrome-crashes",
            "--window-size=1200,1100",
        ]);

        foreach($sources as $source) {
            try {
                $this->scrape($client, $source);
            } catch (Exception $e) {
                Log::error("ScrapeBrowserArticleSources: failed to scrape source {$source->name}: {$e->getMessage()}");
            }
        }

        $client->quit();
    }

    private function scrape(Client $client, ArticleSource $source): void
    {
        $client->request($source->http_method, $source->list_url);

        $client->waitFor($source->list_item_selector);

        $list = $client->getCrawler()->filter($source->list_item_selector);

        $items = $list->each(function($node) use ($source) {
            $title = $node->filter($source->item_title_selector)->text();
            $link = $node->filter($source->item_link_selector)->attr("href");

            return (object) [
                "title" => $title,
                "link" => $link,
            ];
        });

        $items = collect($items)
            ->filter(fn($item) => !!$item->title && !!$item->link)
            ->take(10);
        
        if( $items->isEmpty() ) {
            throw new Exception("List selector not found.");
        }

        foreach($items as $item) {
            $this->scrapeArticle($client, $source, $item);
            sleep(rand(1, 5));
        }
    }

    private function scrapeArticle(Client $client, ArticleSource $source, object $item): void
    {
        $already_scraped = ScrapedArticle::query()
            ->where("article_source_id", $source->id)
            ->where("url", $item->link)
            ->exists();

        if( $already_scraped ) {
            return;
        }

        $client->get($item->link);

        $client->waitFor($source->content_selector);

        /** @var RemoteWebElement $element */
        $element = $client->getCrawler()
            ->filter($source->content_selector)
            ->getElement(0);

        $content = $element->getDomProperty("innerHTML");

        $source_url = parse_url($source->list_url);
        $source_url = $source_url["scheme"] . "://" . $source_url["host"];

        $formatted_content = HtmlFormatter::all($content, $source_url);

        $article = ScrapedArticle::create([
            "article_source_id" => $source->id,
            "url" => $item->link,
            "title" => $item->title,
            "content" => $formatted_content,
        ]);

        (new DownloadScrapedArticleImages)($article);
    }
}
