<?php

use App\Actions\OptimizePendingImages;
use App\Actions\OptimizeStoredImage;
use App\Jobs\OptimizeImageJob;
use App\Models\ArticleSource;
use App\Models\PostImage;
use App\Models\ScrapedArticle;
use App\Models\ScrapedArticleImage;
use App\Models\SgcCompany;
use App\Models\SgcCompanyCustomer;
use App\Models\SgcCustomer;
use App\Models\SgcCustomerAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake("local");
});

it("converte jpeg grande para webp com economia significativa", function () {
    $path = "blog/images/2026/08/30/".Str::uuid()->toString().".jpg";
    $contents = createTestJpeg(width: 2200, height: 1600, quality: 95);

    Storage::disk("local")->put($path, $contents);

    $result = (new OptimizeStoredImage)($path);

    expect($result)->not->toBeNull()
        ->and($result->converted)->toBeTrue()
        ->and($result->path)->toEndWith(".webp")
        ->and($result->optimized_size_bytes)->toBeLessThan($result->original_size_bytes)
        ->and($result->savingsRatio())->toBeGreaterThan(0.05);

    expect(Storage::disk("local")->exists($path))->toBeFalse()
        ->and(Storage::disk("local")->exists($result->path))->toBeTrue();
});

it("preserva o original quando a recompressão não compensa", function () {
    $path = "blog/images/2026/08/30/".Str::uuid()->toString().".jpg";
    $contents = createTestJpeg(width: 800, height: 600, quality: 90);

    Storage::disk("local")->put($path, $contents);

    $first_pass = (new OptimizeStoredImage)($path);

    expect($first_pass)->not->toBeNull();

    $already_efficient_path = "blog/images/2026/08/30/".Str::uuid()->toString().".jpg";
    $webp_contents = Storage::disk("local")->get($first_pass->path);

    Storage::disk("local")->put($already_efficient_path, $webp_contents);

    $result = (new OptimizeStoredImage)($already_efficient_path);

    expect($result)->toBeNull()
        ->and(Storage::disk("local")->exists($already_efficient_path))->toBeTrue();
});

it("ignora arquivos webp pequenos já adequados", function () {
    $source_path = "blog/images/2026/08/30/".Str::uuid()->toString().".jpg";

    Storage::disk("local")->put($source_path, createTestJpeg(width: 800, height: 600, quality: 90));

    $first_pass = (new OptimizeStoredImage)($source_path);

    expect($first_pass)->not->toBeNull();

    $webp_path = $first_pass->path;
    $webp_size = Storage::disk("local")->size($webp_path);

    expect($webp_size)->toBeLessThan(120 * 1024);

    $result = (new OptimizeStoredImage)($webp_path);

    expect($result)->toBeNull()
        ->and(Storage::disk("local")->exists($webp_path))->toBeTrue();
});

it("ignora gif, svg e pdf", function (string $path, string $contents) {
    Storage::disk("local")->put($path, $contents);

    expect((new OptimizeStoredImage)($path))->toBeNull()
        ->and(Storage::disk("local")->exists($path))->toBeTrue();
})->with([
    "gif" => [
        "blog/images/sample.gif",
        base64_decode("R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"),
    ],
    "svg" => [
        "blog/images/sample.svg",
        "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"10\" height=\"10\"><rect width=\"10\" height=\"10\" fill=\"red\"/></svg>",
    ],
    "pdf" => [
        "sgc/customers/1/attachments/sample.pdf",
        "%PDF-1.4 sample",
    ],
]);

it("otimiza PostImage via job e atualiza metadados", function () {
    $path = "blog/images/2026/08/30/".Str::uuid()->toString().".jpg";
    $contents = createTestJpeg(width: 1800, height: 1200, quality: 92);

    Storage::disk("local")->put($path, $contents);

    $image = PostImage::query()->create([
        "path" => "/{$path}",
        "size_bytes" => strlen($contents),
        "original_size_bytes" => strlen($contents),
    ]);

    OptimizeImageJob::dispatchSync($image);

    $image->refresh();

    expect($image->optimized_at)->not->toBeNull()
        ->and($image->path)->toEndWith(".webp")
        ->and($image->size_bytes)->toBeLessThan($image->original_size_bytes)
        ->and(Storage::disk("local")->exists($image->storagePath()))->toBeTrue();
});

it("atualiza mime_type e original_name do anexo SGC após otimização", function () {
    $user = User::factory()->create();

    $company = SgcCompany::query()->create([
        "owner_id" => $user->id,
        "legal_name" => "EMPRESA TESTE LTDA",
        "trade_name" => "EMPRESA TESTE",
        "document" => "12345678000199",
    ]);

    $customer = SgcCustomer::query()->create([
        "name" => "CLIENTE TESTE",
        "identity_document" => "12345678901",
        "tax_id" => "12345678901",
    ]);

    $customer->companies()->attach($company->id);

    $pivot = SgcCompanyCustomer::query()
        ->where("company_id", $company->id)
        ->where("customer_id", $customer->id)
        ->firstOrFail();

    $path = "sgc/customers/{$customer->id}/attachments/2026/08/30/".Str::uuid()->toString().".jpg";
    $contents = createTestJpeg(width: 1600, height: 1200, quality: 93);

    Storage::disk("local")->put($path, $contents);

    $attachment = SgcCustomerAttachment::query()->create([
        "company_customer_id" => $pivot->id,
        "type" => SgcCustomerAttachment::TYPE_OTHER,
        "original_name" => "documento-cliente.jpg",
        "path" => $path,
        "mime_type" => "image/jpeg",
        "size_bytes" => strlen($contents),
        "original_size_bytes" => strlen($contents),
    ]);

    OptimizeImageJob::dispatchSync($attachment);

    $attachment->refresh();

    expect($attachment->optimized_at)->not->toBeNull()
        ->and($attachment->path)->toEndWith(".webp")
        ->and($attachment->mime_type)->toBe("image/webp")
        ->and($attachment->original_name)->toBe("documento-cliente.webp")
        ->and($attachment->size_bytes)->toBeLessThan($attachment->original_size_bytes);
});

it("processa imagens pendentes existentes via backfill", function () {
    $source = ArticleSource::query()->create([
        "driver" => ArticleSource::DRIVER_RSS,
        "name" => "Fonte Teste",
        "list_url" => "https://example.com/feed",
        "list_item_selector" => "item",
        "item_title_selector" => "title",
        "item_link_selector" => "link",
        "content_selector" => "content",
    ]);

    $article = ScrapedArticle::query()->create([
        "article_source_id" => $source->id,
        "url" => "https://example.com/post-1",
        "title" => "Artigo teste",
        "content" => "<p>conteudo</p>",
    ]);

    $blog_path = "blog/images/2026/08/30/".Str::uuid()->toString().".jpg";
    $scraped_path = "scraped-articles/images/2026/08/30/".Str::uuid()->toString().".jpg";

    $blog_contents = createTestJpeg(width: 1500, height: 1000, quality: 90);
    $scraped_contents = createTestJpeg(width: 1500, height: 1000, quality: 90);

    Storage::disk("local")->put($blog_path, $blog_contents);
    Storage::disk("local")->put($scraped_path, $scraped_contents);

    PostImage::query()->create([
        "path" => "/{$blog_path}",
        "size_bytes" => strlen($blog_contents),
        "original_size_bytes" => strlen($blog_contents),
    ]);

    ScrapedArticleImage::query()->create([
        "scraped_article_id" => $article->id,
        "path" => "/{$scraped_path}",
        "size_bytes" => strlen($scraped_contents),
        "original_size_bytes" => strlen($scraped_contents),
    ]);

    $processed = (new OptimizePendingImages)(limit: 10, sync: true);

    expect($processed)->toBe(2)
        ->and(PostImage::query()->whereNull("optimized_at")->count())->toBe(0)
        ->and(ScrapedArticleImage::query()->whereNull("optimized_at")->count())->toBe(0);
});

function createTestJpeg(int $width, int $height, int $quality = 90): string
{
    $image = imagecreatetruecolor($width, $height);

    for ($x = 0; $x < $width; $x += 8) {
        for ($y = 0; $y < $height; $y += 8) {
            $color = imagecolorallocate(
                $image,
                ($x + $y) % 256,
                ($x * 2) % 256,
                ($y * 3) % 256,
            );

            imagefilledrectangle($image, $x, $y, min($x + 7, $width - 1), min($y + 7, $height - 1), $color);
        }
    }

    ob_start();
    imagejpeg($image, null, $quality);

    $contents = ob_get_clean() ?: "";

    return $contents;
}
