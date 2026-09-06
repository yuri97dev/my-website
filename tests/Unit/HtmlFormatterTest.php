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

it("copia href quando ancora envolve figure (a > figure > img)", function () {
    $html = <<<'HTML'
<a href="https://assets-prd.ignimgs.com/2026/09/05/skytech-gaming-blaze-5-review-4-1788568565865.jpg">
<figure>
<img alt="" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7">
</figure>
</a>
HTML;

    $result = HtmlFormatter::resolveLazyLoadedImages($html);

    expect($result)->toContain('src="https://assets-prd.ignimgs.com/2026/09/05/skytech-gaming-blaze-5-review-4-1788568565865.jpg"')
        ->toContain("<figure>")
        ->not->toContain("<a ");
});

it("resolve multiplas imagens lazy em ancoras irmaas", function () {
    $html = <<<'HTML'
<a href="https://assets-prd.ignimgs.com/2026/09/05/image-1.jpg"><figure><img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"></figure></a>
<a href="https://assets-prd.ignimgs.com/2026/09/05/image-2.jpg"><figure><img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"></figure></a>
HTML;

    $result = HtmlFormatter::resolveLazyLoadedImages($html);

    expect($result)->toContain('src="https://assets-prd.ignimgs.com/2026/09/05/image-1.jpg"')
        ->toContain('src="https://assets-prd.ignimgs.com/2026/09/05/image-2.jpg"')
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

it("copia href mesmo sem extensao de arquivo", function () {
    $html = <<<'HTML'
<a href="https://cdn.example.com/550e8400-e29b-41d4-a716-446655440000">
<figure>
<img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7">
</figure>
</a>
HTML;

    $result = HtmlFormatter::resolveLazyLoadedImages($html);

    expect($result)->toContain('src="https://cdn.example.com/550e8400-e29b-41d4-a716-446655440000"')
        ->not->toContain("<a ");
});

it("nao altera ancora sem imagem lazy", function () {
    $html = '<a href="https://example.com/article">Read more</a>';

    $result = HtmlFormatter::resolveLazyLoadedImages($html);

    expect($result)->toBe($html);
});
