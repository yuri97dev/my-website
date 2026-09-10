<?php

use App\Actions\PurgeStalePosts;
use App\Models\Post;
use App\Models\PostImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake("local");
});

function createTestPost(array $attributes = []): Post
{
    return Post::create(array_merge([
        "slug" => Str::slug(Str::uuid()->toString()),
        "title" => "Post de teste",
        "content" => "<p>Conteúdo</p>",
        "status" => Post::STATUS_DRAFT,
    ], $attributes));
}

it("remove permanentemente posts excluídos há mais de 7 dias", function () {
    $post = createTestPost(["status" => Post::STATUS_PUBLISHED]);
    $post->delete();

    Post::onlyTrashed()
        ->whereKey($post->id)
        ->update(["deleted_at" => now()->subDays(8)]);

    $purged = (new PurgeStalePosts)();

    expect($purged)->toBe(1)
        ->and(Post::withTrashed()->find($post->id))->toBeNull();
});

it("remove permanentemente rascunhos sem alteração há mais de 7 dias", function () {
    $post = createTestPost();

    $post->forceFill(["updated_at" => now()->subDays(8)])->save();

    $purged = (new PurgeStalePosts)();

    expect($purged)->toBe(1)
        ->and(Post::query()->find($post->id))->toBeNull();
});

it("preserva posts excluídos recentemente", function () {
    $post = createTestPost(["status" => Post::STATUS_PUBLISHED]);
    $post->delete();

    expect((new PurgeStalePosts)())->toBe(0)
        ->and(Post::onlyTrashed()->find($post->id))->not->toBeNull();
});

it("preserva rascunhos recentes", function () {
    $post = createTestPost();

    expect((new PurgeStalePosts)())->toBe(0)
        ->and(Post::query()->find($post->id))->not->toBeNull();
});

it("preserva posts publicados antigos", function () {
    $post = createTestPost([
        "status" => Post::STATUS_PUBLISHED,
        "published_at" => now()->subDays(30),
    ]);

    $post->forceFill(["updated_at" => now()->subDays(30)])->save();

    expect((new PurgeStalePosts)())->toBe(0)
        ->and(Post::query()->find($post->id))->not->toBeNull();
});

it("remove arquivos de imagens associadas ao post", function () {
    $post = createTestPost();
    $post->forceFill(["updated_at" => now()->subDays(8)])->save();

    $image_path = "blog/images/2026/09/09/".Str::uuid()->toString().".webp";

    Storage::disk("local")->put($image_path, "conteudo-falso");

    PostImage::create([
        "post_id" => $post->id,
        "path" => "/{$image_path}",
        "size_bytes" => 14,
    ]);

    $thumbnail_path = "blog/images/2026/09/09/".Str::uuid()->toString().".webp";

    Storage::disk("local")->put($thumbnail_path, "miniatura-falsa");

    $post->update(["thumbnail_path" => "/{$thumbnail_path}"]);

    (new PurgeStalePosts)();

    expect(Storage::disk("local")->exists($image_path))->toBeFalse()
        ->and(Storage::disk("local")->exists($thumbnail_path))->toBeFalse()
        ->and(PostImage::query()->count())->toBe(0);
});

it("respeita o limite por execução", function () {
    $first = createTestPost(["slug" => "rascunho-antigo-1"]);
    $first->forceFill(["updated_at" => now()->subDays(8)])->save();

    $second = createTestPost(["slug" => "rascunho-antigo-2"]);
    $second->forceFill(["updated_at" => now()->subDays(8)])->save();

    $purged = (new PurgeStalePosts)(limit: 1);

    expect($purged)->toBe(1)
        ->and(Post::query()->count())->toBe(1);
});
