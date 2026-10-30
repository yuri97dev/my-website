<?php

use App\Actions\ProcessScrapedArticlesWithAi;
use App\Jobs\TranslateScrapedArticleJob;
use App\Models\AiPrompt;
use App\Models\ArticleSource;
use App\Models\Post;
use App\Models\ScrapedArticle;
use App\Models\User;
use App\Support\AssistantPrompts;
use Database\Seeders\AssistantAiPromptSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    config([
        "database.default" => "sqlite",
        "database.connections.sqlite.database" => ":memory:",
        "services.ollama.base_url" => "http://ollama.test/api",
        "services.ollama.chat_model" => "test-model",
    ]);

    DB::purge("sqlite");
    Cache::flush();
    Http::preventStrayRequests();

    Schema::create("posts", function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger("author_id")->nullable();
        $table->unsignedBigInteger("category_id")->nullable();
        $table->string("slug")->nullable();
        $table->string("title");
        $table->string("status");
        $table->text("content")->nullable();
        $table->text("excerpt")->nullable();
        $table->string("meta_description")->nullable();
        $table->string("thumbnail_path")->nullable();
        $table->timestamp("published_at")->nullable();
        $table->softDeletes();
        $table->timestamps();
    });

    Schema::create("post_categories", function (Blueprint $table) {
        $table->id();
        $table->string("slug")->unique();
        $table->string("name");
        $table->timestamps();
    });

    Schema::create("post_tags", function (Blueprint $table) {
        $table->id();
        $table->string("slug")->unique();
        $table->string("name");
        $table->timestamps();
    });

    Schema::create("post_tag", function (Blueprint $table) {
        $table->foreignId("post_id")->constrained();
        $table->foreignId("post_tag_id")->constrained("post_tags");
        $table->primary(["post_id", "post_tag_id"]);
    });
});

function prepareArticles(int $count): ArticleSource
{
    foreach( [
        "0001_01_01_000000_create_users_table.php",
        "2026_06_24_230132_create_ai_providers_table.php",
        "2026_09_09_200000_add_is_system_to_ai_prompts_table.php",
        "2026_06_20_205342_create_article_sources.php",
        "2026_07_28_234500_create_article_source_ai_prompts_table.php",
    ] as $file ) {
        (require database_path("migrations/{$file}"))->up();
    }

    Schema::table("scraped_articles", function (Blueprint $table) {
        $table->unsignedSmallInteger("translation_attempts")->default(0);
        $table->unsignedBigInteger("processing_by")->nullable();
        $table->timestamp("processing_at")->nullable();
    });

    $author = User::factory()->create();
    config([
        "assistant.enabled" => true,
        "assistant.author_id" => $author->id,
    ]);

    (new AssistantAiPromptSeeder)->run();

    $source = ArticleSource::create([
        "driver" => "rss",
        "name" => "Fonte",
        "list_url" => "https://example.test/feed",
        "list_item_selector" => "item",
        "item_title_selector" => "title",
        "item_link_selector" => "link",
        "content_selector" => "article",
    ]);

    $source->aiPrompts()->attach(
        AiPrompt::query()->where("key", AssistantPrompts::KEY_SELECT_SYSTEM)->value("id"),
    );

    foreach( range(1, $count) as $number ) {
        $source->articles()->create([
            "url" => "https://example.test/{$number}",
            "title" => "Notícia {$number}",
            "content" => "<p>Alpha sentence. Beta sentence. Gamma sentence. Delta sentence.</p>",
        ]);
    }

    Bus::fake([TranslateScrapedArticleJob::class]);

    return $source;
}

it("escolhe um artigo em lotes de cinco e envia só três sentenças", function () {
    prepareArticles(6);

    $calls = 0;

    Http::fake(function () use (&$calls) {
        $calls++;

        return Http::response(["message" => ["content" => $calls === 1 ? "1" : "2"]]);
    });

    (new ProcessScrapedArticlesWithAi)();

    $messages = Http::recorded()->map(fn ($pair) => $pair[0]["messages"])->all();

    expect($messages)->toHaveCount(2)
        ->and($messages[0][0]["content"])->toContain("Saída: apenas o número")
        ->and($messages[0][1]["content"])->toContain("1. Title: Notícia 6")
        ->and($messages[0][1]["content"])->toContain("5. Title: Notícia 2")
        ->and($messages[0][1]["content"])->toContain("Alpha sentence. Beta sentence. Gamma sentence.")
        ->and($messages[0][1]["content"])->not->toContain("Delta sentence")
        ->and($messages[0][1]["content"])->not->toContain("Notícia 1")
        ->and($messages[1][1]["content"])->toContain("1. Title: Notícia 6")
        ->and($messages[1][1]["content"])->toContain("2. Title: Notícia 1");

    $chosen = ScrapedArticle::query()->where("title", "Notícia 1")->firstOrFail();

    expect($calls)->toBe(2)
        ->and($chosen->processing_by)->toBe(User::ASSISTANT_ID)
        ->and(ScrapedArticle::query()->whereNotNull("processing_by")->count())->toBe(1);

    Bus::assertDispatchedTimes(TranslateScrapedArticleJob::class, 1);
});

it("usa apenas os cinquenta artigos mais recentes até restar um", function () {
    prepareArticles(51);

    Http::fake([
        "ollama.test/*" => Http::response(["message" => ["content" => "1"]]),
    ]);

    (new ProcessScrapedArticlesWithAi)();

    Http::assertSentCount(13);

    foreach( Http::recorded() as [$request] ) {
        expect($request["messages"][1]["content"])->not->toContain("Title: Notícia 1\n");
    }

    expect(ScrapedArticle::query()->where("title", "Notícia 51")->value("processing_by"))
        ->toBe(User::ASSISTANT_ID);
});

it("não inicia tradução quando a escolha é inválida ou já existe uma tradução pendente", function () {
    prepareArticles(5);

    Http::fake([
        "ollama.test/*" => Http::response(["message" => ["content" => "9"]]),
    ]);

    (new ProcessScrapedArticlesWithAi)();

    expect(ScrapedArticle::query()->whereNotNull("processing_by")->count())->toBe(0);
    Bus::assertNotDispatched(TranslateScrapedArticleJob::class);

    ScrapedArticle::query()->first()->update([
        "processing_by" => User::ASSISTANT_ID,
        "processing_at" => now(),
    ]);

    (new ProcessScrapedArticlesWithAi)();

    Http::assertSentCount(1);
});

it("grava o artigo traduzido como rascunho do ajudante", function () {
    prepareArticles(1);

    $article = ScrapedArticle::query()->firstOrFail();
    $article->update([
        "processing_by" => User::ASSISTANT_ID,
        "processing_at" => now(),
        "translated_title" => "Título traduzido",
        "translated_content" => "<p>Texto traduzido.</p><img src=\"https://cdn.test/thumb.jpg\">",
    ]);

    Http::fake([
        "ollama.test/*" => Http::response(["message" => ["content" => json_encode([
            "category" => "Games",
            "excerpt" => "Resumo curto",
            "meta_description" => "Descricao curta",
            "tags" => ["Anime", "RPG"],
        ])]]),
    ]);

    (new ProcessScrapedArticlesWithAi)();

    $post = Post::query()->firstOrFail();
    $article->refresh();

    expect($post->status)->toBe(Post::STATUS_DRAFT)
        ->and($post->author_id)->toBe(config("assistant.author_id"))
        ->and($post->title)->toBe("Título traduzido")
        ->and($post->content)->toBe($article->translated_content)
        ->and($post->excerpt)->toBe("Resumo curto")
        ->and($post->thumbnail_path)->toBe("https://cdn.test/thumb.jpg")
        ->and($post->category->name)->toBe("Games")
        ->and($post->tags->pluck("name")->sort()->values()->all())->toBe(["Anime", "RPG"])
        ->and($article->post_id)->toBe($post->id)
        ->and($article->processing_by)->toBeNull();

    Bus::assertNotDispatched(TranslateScrapedArticleJob::class);
    Http::assertSent(fn ($request) => str_contains($request["messages"][0]["content"], "Categorias: nenhuma"));
});
