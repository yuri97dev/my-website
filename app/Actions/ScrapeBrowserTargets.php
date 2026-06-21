<?php

namespace App\Actions;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Panther\Client;
use Facebook\WebDriver\Remote\RemoteWebElement;
use App\Models\ScrapeTarget;
use App\Models\ScrapeItem;
use App\Helpers\HtmlFormatter;
use Exception;

class ScrapeBrowserTargets
{
    public function __invoke(): void
    {
        $targets = ScrapeTarget::driver("browser")
            ->enabled()
            ->get();

        if( $targets->isEmpty() ) {
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

        foreach($targets as $target) {
            try {
                $this->scrape($client, $target);
            } catch (Exception $e) {
                Log::error("ScrapeBrowserTargets: failed to scrape target {$target->name}: {$e->getMessage()}");
            }
        }

        $client->quit();
    }

    private function scrape(Client $client, ScrapeTarget $target): void
    {
        $client->request($target->http_method, $target->list_url);

        $client->waitFor($target->list_item_selector);

        $list = $client->getCrawler()->filter($target->list_item_selector);

        $items = $list->each(function($node) use ($target) {
            $title = $node->filter($target->item_title_selector)->text();
            $link = $node->filter($target->item_link_selector)->attr("href");

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
            $this->scrapeItem($client, $target, $item);
            sleep(rand(1, 5));
        }
    }

    private function scrapeItem(Client $client, ScrapeTarget $target, object $item): void
    {
        $already_scraped = ScrapeItem::query()
            ->where("scrape_target_id", $target->id)
            ->where("url", $item->link)
            ->exists();

        if( $already_scraped ) {
            return;
        }

        $client->get($item->link);

        $client->waitFor($target->content_selector);

        /** @var RemoteWebElement $element */
        $element = $client->getCrawler()
            ->filter($target->content_selector)
            ->getElement(0);

        $content = $element->getDomProperty("innerHTML");

        $formatted_content = HtmlFormatter::all($content);

        ScrapeItem::create([
            "scrape_target_id" => $target->id,
            "url" => $item->link,
            "title" => $item->title,
            "content" => $formatted_content,
        ]);
    }
}
