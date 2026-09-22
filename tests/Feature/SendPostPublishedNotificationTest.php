<?php

use App\Jobs\SendPostPublishedNotificationJob;
use App\Models\NotificationDelivery;
use App\Models\NotificationProvider;
use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function createNotificationCategory(string $slug = "animes"): PostCategory
{
    return PostCategory::create([
        "slug" => $slug,
        "name" => ucfirst($slug),
    ]);
}

function createDiscordProvider(string $key, string $webhook_url): NotificationProvider
{
    return NotificationProvider::create([
        "key" => $key,
        "channel" => NotificationProvider::CHANNEL_DISCORD,
        "name" => "Discord {$key}",
        "config" => [
            "webhook_url" => $webhook_url,
        ],
        "is_enabled" => true,
    ]);
}

it("envia notificação no discord ao publicar um post novo", function () {
    Http::fake();

    $category = createNotificationCategory();

    $webhook_url = "https://discord.com/api/webhooks/123/token";

    createDiscordProvider($category->slug, $webhook_url);

    $post = Post::create([
        "category_id" => $category->id,
        "slug" => "novo-anime-da-temporada",
        "title" => "Novo anime da temporada",
        "excerpt" => "Confira o resumo da matéria no site.",
        "content" => "<p>Conteúdo completo</p>",
        "status" => Post::STATUS_PUBLISHED,
        "published_at" => now(),
    ]);

    (new SendPostPublishedNotificationJob($post->id))->handle(
        app(\App\Services\Notifications\Discord\PostPublishedDiscordNotifier::class),
    );

    Http::assertSent(function ($request) use ($webhook_url, $post, $category) {
        $embed = $request->data()["embeds"][0] ?? [];

        return $request->url() === $webhook_url
            && $embed["title"] === $post->title
            && $embed["description"] === $post->excerpt
            && $embed["url"] === route("blog.show", [$category->slug, $post->slug]);
    });

    expect(NotificationDelivery::query()->count())->toBe(1)
        ->and(NotificationDelivery::first()->status)->toBe(NotificationDelivery::STATUS_SENT);
});

it("não envia notificação quando não há provider para a categoria", function () {
    Http::fake();

    $category = createNotificationCategory("jogos");

    $post = Post::create([
        "category_id" => $category->id,
        "slug" => Str::slug(Str::uuid()->toString()),
        "title" => "Review de jogo",
        "excerpt" => "Resumo do jogo.",
        "content" => "<p>Conteúdo</p>",
        "status" => Post::STATUS_PUBLISHED,
        "published_at" => now(),
    ]);

    (new SendPostPublishedNotificationJob($post->id))->handle(
        app(\App\Services\Notifications\Discord\PostPublishedDiscordNotifier::class),
    );

    Http::assertNothingSent();

    expect(NotificationDelivery::query()->count())->toBe(0);
});

it("criptografa a config do provider ao salvar", function () {
    $provider = createDiscordProvider("animes", "https://discord.com/api/webhooks/secret/token");

    $raw = \Illuminate\Support\Facades\DB::table("notification_providers")
        ->where("id", $provider->id)
        ->value("config");

    expect($raw)->not->toContain("secret")
        ->and($provider->fresh()->config["webhook_url"])
        ->toBe("https://discord.com/api/webhooks/secret/token");
});
