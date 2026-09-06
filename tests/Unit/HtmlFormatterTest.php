<?php

use App\Helpers\HtmlFormatter;

it("copia href do link pai quando img usa placeholder data:image", function () {
    $html = <<<'HTML'
<figure>
<a href="https://assets-prd.ignimgs.com/2026/09/04/adria-arjona.png">
<img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" />
</a>
</figure>
HTML;

    $result = HtmlFormatter::resolveLazyLoadedImages($html);

    expect($result)->toContain('src="https://assets-prd.ignimgs.com/2026/09/04/adria-arjona.png"')
        ->not->toContain("<a ");
});

it("mantem src real quando img ja tem url http", function () {
    $html = '<a href="https://example.com/other.png"><img src="https://example.com/photo.png" /></a>';

    $result = HtmlFormatter::resolveLazyLoadedImages($html);

    expect($result)->toContain('src="https://example.com/photo.png"')
        ->not->toContain('src="https://example.com/other.png"');
});

it("continua resolvendo data-src", function () {
    $html = '<img data-src="https://example.com/lazy.png" />';

    $result = HtmlFormatter::resolveLazyLoadedImages($html);

    expect($result)->toContain('src="https://example.com/lazy.png"');
});

it("nao copia href quando ancora nao esta dentro de figure", function () {
    $html = <<<'HTML'
<a href="https://example.com/article">
<img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" />
</a>
HTML;

    $result = HtmlFormatter::resolveLazyLoadedImages($html);

    expect($result)->toContain('src="data:image/gif;base64,')
        ->not->toContain('src="https://example.com/article"');
});
