<?php

use App\Actions\ScrapeRssArticleSources;
use App\Models\ArticleSource;
use App\Models\ScrapeHttpProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it("extrai itens RSS com titulos em CDATA", function () {
    ScrapeHttpProfile::query()->create([
        "name" => "Test Profile",
        "headers" => [
            "User-Agent" => "Mozilla/5.0 Test",
        ],
        "is_enabled" => true,
    ]);

    Http::fake([
        "https://example.com/feed" => Http::response(<<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
<channel>
<item>
<title><![CDATA[The Batman Part 2]]></title>
<link>https://example.com/article-1</link>
</item>
</channel>
</rss>
XML, 200, ["Content-Type" => "application/xml"]),
        "https://example.com/article-1" => Http::response(
            "<html><body><section class=\"article-page\"><p>Content</p></section></body></html>",
            200,
            ["Content-Type" => "text/html"],
        ),
    ]);

    $source = new ArticleSource([
        "driver" => ArticleSource::DRIVER_RSS,
        "http_method" => "GET",
        "list_url" => "https://example.com/feed",
        "list_item_selector" => "channel.item",
        "item_title_selector" => "title",
        "item_link_selector" => "link",
        "content_selector" => "section.article-page",
    ]);

    $result = (new ScrapeRssArticleSources)->scrapeOne($source);

    expect($result["title"])->toBe("The Batman Part 2")
        ->and($result["url"])->toBe("https://example.com/article-1");
});
