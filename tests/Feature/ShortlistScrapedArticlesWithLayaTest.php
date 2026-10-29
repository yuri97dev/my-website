<?php

use App\Actions\ProcessScrapedArticlesWithAi;
use App\Actions\ShortlistScrapedArticlesWithLaya;
use App\Jobs\TranslateScrapedArticleJob;
use App\Jobs\GenerateArticleShortlistJob;
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
        "services.laya.url" => "http://laya.test",
        "services.laya.key" => "test-key",
        "services.ollama.base_url" => "http://ollama.test/api",
        "services.ollama.chat_model" => "test-model",
    ]);

    DB::purge("sqlite");
    Cache::flush();
    Http::preventStrayRequests();

    Schema::create("posts", function (Blueprint $table) {
        $table->id();
        $table->string("title");
        $table->string("status");
        $table->text("content")->nullable();
        $table->text("excerpt")->nullable();
        $table->timestamp("published_at")->nullable();
        $table->softDeletes();
        $table->timestamps();
    });
});

function layaArticle(int $id): ScrapedArticle
{
    return (new ScrapedArticle)->forceFill([
        "id" => $id,
        "title" => "Artigo {$id}",
        "content" => "<p>" . str_repeat("Resumo técnico. ", 100) . "</p>",
    ]);
}

function layaEvaluation(float $affinity = 0.9, array $choices = []): array
{
    return [
        "answers" => [
            "affinity" => [
                "type" => "choice",
                "choice" => $choices["affinity"] ?? "high",
                "probabilities" => ["high" => $affinity, "partial" => 1 - $affinity, "outside" => 0],
            ],
            "editorial" => [
                "type" => "choice",
                "choice" => $choices["editorial"] ?? "allowed",
                "probabilities" => ["allowed" => 0.9, "restricted" => 0.1],
            ],
            "promotion" => [
                "type" => "choice",
                "choice" => $choices["promotion"] ?? "informational",
                "probabilities" => ["informational" => 0.9, "promotional" => 0.1],
            ],
        ],
        "usage" => ["truncated" => false],
    ];
}

it("usa apenas os títulos dos 20 posts publicados mais recentes como referência", function () {
    $posts = collect();

    foreach( range(22, 1) as $days ) {
        $posts->push(Post::create([
            "title" => "Publicado há {$days} dias",
            "status" => Post::STATUS_PUBLISHED,
            "published_at" => now()->subDays($days),
            "content" => "Conteúdo privado da referência",
            "excerpt" => "Resumo que não deve ser enviado",
        ]));
    }

    foreach( [
        ["status" => Post::STATUS_DRAFT, "published_at" => now()->subHour()],
        ["status" => Post::STATUS_ARCHIVED, "published_at" => now()->subHour()],
        ["status" => Post::STATUS_PUBLISHED, "published_at" => now()->addDay()],
        ["status" => Post::STATUS_PUBLISHED, "published_at" => null],
        ["status" => Post::STATUS_PUBLISHED, "published_at" => now()->subMinute(), "deleted_at" => now()],
    ] as $attributes ) {
        (new Post)->forceFill(["title" => "Não enviar"] + $attributes)->save();
    }

    Http::fake(["laya.test/*" => Http::response(["results" => [layaEvaluation()]])]);

    (new ShortlistScrapedArticlesWithLaya)(collect([layaArticle(1)]), 10);

    $expected_titles = $posts->reverse()->take(20)->pluck("title")->values()->all();

    Http::assertSent(function ($request) use ($expected_titles) {
        $state = $request["states"][0];

        return $request->url() === "http://laya.test/v1/systemone/batch"
            && $request->hasHeader("Authorization", "Bearer test-key")
            && $state["recent_published_titles"] === $expected_titles
            && array_keys($state) === ["candidate", "recent_published_titles"]
            && array_keys($state["candidate"]) === ["title", "summary"]
            && mb_strlen($state["candidate"]["summary"]) === 600
            && !str_contains(json_encode($state), "Conteúdo privado")
            && !str_contains(json_encode($state), "Resumo que")
            && $request["max_len"] === 4096
            && count($request["questions"]) === 3;
    });
});

it("filtra pautas restritas, promoções e assuntos fora do nicho e ordena os aprovados", function () {
    config(["assistant.laya.request_size" => 64]);
    Http::fake(["laya.test/*" => Http::response(["results" => [
        layaEvaluation(0.6),
        layaEvaluation(0.95, ["editorial" => "restricted"]),
        layaEvaluation(0.9),
        layaEvaluation(0.99, ["promotion" => "promotional"]),
        layaEvaluation(0.1, ["affinity" => "outside"]),
        layaEvaluation(0.8),
        layaEvaluation(0.9),
    ]])]);

    $result = (new ShortlistScrapedArticlesWithLaya)(collect(range(1, 7))->map(fn ($id) => layaArticle($id)), 3);

    expect($result->pluck("id")->all())->toBe([3, 7, 6]);
    Http::assertSentCount(1);
});

it("funciona sem posts publicados e permite menos de dez candidatos", function () {
    Http::fake(["laya.test/*" => Http::response(["results" => [layaEvaluation()]])]);

    $result = (new ShortlistScrapedArticlesWithLaya)(collect([layaArticle(1)]), 10);

    expect($result->pluck("id")->all())->toBe([1]);
    Http::assertSent(fn ($request) => $request["states"][0]["recent_published_titles"] === []);
});

it("não consulta o Laya quando não há candidatos", function () {
    Http::fake();

    expect((new ShortlistScrapedArticlesWithLaya)(collect(), 10))->toBeEmpty();
    Http::assertNothingSent();
});

it("usa limites válidos quando a configuração está ausente ou inválida", function ($value) {
    config(["assistant.laya" => []]);
    if( $value !== "missing" ) {
        config(["assistant.laya.max_len" => $value, "assistant.laya.timeout" => $value]);
    }
    $sent_timeout = null;
    Http::fake(function ($request, $options) use (&$sent_timeout) {
        $sent_timeout = $options["timeout"];
        return Http::response(["results" => [layaEvaluation()]]);
    });

    (new ShortlistScrapedArticlesWithLaya)(collect([layaArticle(1)]), 10);

    Http::assertSent(fn ($request) => $request["max_len"] === 4096);
    expect($sent_timeout)->toBe(90);
})->with(["missing", null, 0, -1, "invalid"]);

it("divide pools maiores que o limite de 64 estados mantendo a ordem das respostas", function () {
    config(["assistant.laya.request_size" => 64]);
    Http::fakeSequence()
        ->push(["results" => array_fill(0, 64, layaEvaluation(0.7))])
        ->push(["results" => [layaEvaluation(0.99)]]);

    $result = (new ShortlistScrapedArticlesWithLaya)(collect(range(1, 65))->map(fn ($id) => layaArticle($id)), 2);

    expect($result->pluck("id")->all())->toBe([65, 1]);
    Http::assertSentCount(2);
});

it("avalia o pool de 50 em dez chamadas de cinco candidatos mantendo os dez melhores", function () {
    Http::fake(function ($request) {
        expect(count($request["states"]))->toBe(5);
        return Http::response(["results" => array_map(
            fn ($state) => layaEvaluation(((int) substr($state["candidate"]["title"], 7)) / 100 + 0.5),
            $request["states"],
        )]);
    });
    $result = (new ShortlistScrapedArticlesWithLaya)(collect(range(1, 50))->map(fn ($id) => layaArticle($id)), 10);
    expect($result->pluck("id")->all())->toBe(range(50, 41));
    Http::assertSentCount(10);
});

it("interrompe a seleção se a resposta do Laya for inválida ou perder contexto", function ($path, $value) {
    $payload = ["results" => [layaEvaluation()]];
    data_set($payload, $path, $value);
    Http::fake(["laya.test/*" => Http::response($payload)]);

    expect(fn () => (new ShortlistScrapedArticlesWithLaya)(collect([layaArticle(1)]), 10))
        ->toThrow(RuntimeException::class);
})->with([
    "lote ausente" => ["results", null],
    "quantidade incorreta" => ["results", []],
    "pergunta ausente" => ["results.0.answers.editorial", null],
    "categoria desconhecida" => ["results.0.answers.affinity.choice", "unknown"],
    "probabilidade inválida" => ["results.0.answers.affinity.probabilities.high", -1],
    "distribuição inválida" => ["results.0.answers.affinity.probabilities.high", 0.2],
    "contexto truncado" => ["results.0.usage.truncated", true],
]);

function prepareAssistantSelection(): void
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
        $table->text("assistant_selection_prompt")->nullable();
    });

    $author = User::factory()->create();
    config([
        "assistant.enabled" => true,
        "assistant.author_id" => $author->id,
        "assistant.store_selection_prompt" => true,
    ]);

    (new AssistantAiPromptSeeder)->run();
    $prompt = AiPrompt::query()->where("key", AssistantPrompts::KEY_SELECT_SYSTEM)->firstOrFail();
    // O fluxo também precisa respeitar prompts antigos já salvos no banco.
    $prompt->update(["content" => "Selecione um entre 10 artigos. Saída: apenas o número (1-10)."]);
    $source = ArticleSource::create([
        "driver" => "rss",
        "name" => "Fonte",
        "list_url" => "https://example.test/feed",
        "list_item_selector" => "item",
        "item_title_selector" => "title",
        "item_link_selector" => "link",
        "content_selector" => "article",
    ]);
    $source->aiPrompts()->attach($prompt->id);

    foreach( range(1, 3) as $number ) {
        $source->articles()->create([
            "url" => "https://example.test/{$number}",
            "title" => "Notícia {$number}",
            "content" => "<p>Uma notícia sobre programação.</p>",
        ]);
    }

    Bus::fake([TranslateScrapedArticleJob::class]);
}

it("envia apenas os aprovados ao Ollama e bloqueia para tradução o artigo escolhido", function () {
    prepareAssistantSelection();
    Http::fake([
        "laya.test/*" => Http::response(["results" => [
            layaEvaluation(0.99, ["promotion" => "promotional"]),
            layaEvaluation(0.95),
            layaEvaluation(0.8),
        ]]),
        "ollama.test/*" => Http::response(["message" => ["content" => "2"]]),
    ]);

    (new ProcessScrapedArticlesWithAi)();

    Http::assertSent(function ($request) {
        if( !str_contains($request->url(), "ollama.test") ) return false;

        return str_contains($request["messages"][0]["content"], "Neste lote há 2 artigos")
            && str_contains($request["messages"][1]["content"], "1. Title: Notícia 2")
            && str_contains($request["messages"][1]["content"], "2. Title: Notícia 1")
            && !str_contains($request["messages"][1]["content"], "Notícia 3");
    });

    $chosen = ScrapedArticle::query()->where("title", "Notícia 1")->firstOrFail();
    expect($chosen->processing_by)->toBe(User::ASSISTANT_ID)
        ->and($chosen->assistant_selection_prompt)->toContain("Neste lote há 2 artigos")
        ->and(ScrapedArticle::query()->whereNotNull("processing_by")->count())->toBe(1);
    Bus::assertDispatchedTimes(TranslateScrapedArticleJob::class, 1);
});

it("reduz a pré-seleção em lotes de cinco até restar um artigo", function () {
    prepareAssistantSelection();
    $source = ArticleSource::query()->firstOrFail();

    foreach( range(4, 6) as $number ) {
        $source->articles()->create([
            "url" => "https://example.test/{$number}",
            "title" => "Notícia {$number}",
            "content" => "<p>Uma notícia sobre programação.</p>",
        ]);
    }

    $ollama_calls = 0;

    Http::fake(function ($request) use (&$ollama_calls) {
        if( str_contains($request->url(), "laya.test") ) {
            return Http::response([
                "results" => array_fill(0, count($request["states"]), layaEvaluation()),
            ]);
        }

        $ollama_calls++;
        $system = $request["messages"][0]["content"];
        $user = $request["messages"][1]["content"];

        if( $ollama_calls === 1 ) {
            expect($system)->toContain("Neste lote há 5 artigos")
                ->and($user)->toContain("1. Title: Notícia 6")
                ->and($user)->toContain("5. Title: Notícia 2")
                ->and($user)->not->toContain("Notícia 1");

            return Http::response(["message" => ["content" => "1"]]);
        }

        expect($system)->toContain("Neste lote há 2 artigos")
            ->and($user)->toContain("1. Title: Notícia 6")
            ->and($user)->toContain("2. Title: Notícia 1");

        return Http::response(["message" => ["content" => "2"]]);
    });

    (new ProcessScrapedArticlesWithAi)();

    $chosen = ScrapedArticle::query()->where("title", "Notícia 1")->firstOrFail();

    expect($ollama_calls)->toBe(2)
        ->and($chosen->processing_by)->toBe(User::ASSISTANT_ID)
        ->and($chosen->assistant_selection_prompt)->toContain("Neste lote há 5 artigos")
        ->and($chosen->assistant_selection_prompt)->toContain("Neste lote há 2 artigos")
        ->and(ScrapedArticle::query()->whereNotNull("processing_by")->count())->toBe(1);
    Bus::assertDispatchedTimes(TranslateScrapedArticleJob::class, 1);
});

it("não chama o Ollama nem inicia tradução quando o filtro reprova todos ou falha", function ($status) {
    prepareAssistantSelection();
    Http::fake([
        "laya.test/*" => Http::response(["results" => array_fill(0, 3, layaEvaluation(0.9, ["editorial" => "restricted"]))], $status),
    ]);

    (new ProcessScrapedArticlesWithAi)();

    Http::assertSentCount(1);
    expect(ScrapedArticle::query()->whereNotNull("processing_by")->count())->toBe(0);
    Bus::assertNotDispatched(TranslateScrapedArticleJob::class);
})->with([200, 500]);

it("reutiliza a busca dos candidatos recentes respeitando os limites configurados", function () {
    prepareAssistantSelection();
    config(["assistant.pool_size" => 2, "assistant.selection_size" => 1]);
    Http::fake(["laya.test/*" => Http::response(["results" => [layaEvaluation(0.8), layaEvaluation(0.95)]])]);

    $result = (new ShortlistScrapedArticlesWithLaya)->recent();

    expect($result->pluck("title")->all())->toBe(["Notícia 2"]);
    Http::assertSent(fn ($request) => array_column(array_column($request["states"], "candidate"), "title") === ["Notícia 3", "Notícia 2"]);
    Bus::assertNotDispatched(TranslateScrapedArticleJob::class);
});

it("expõe a pré-seleção na rota de admin sem iniciar tradução", function () {
    prepareAssistantSelection();
    Http::fake(["laya.test/*" => Http::response(["results" => array_fill(0, 3, layaEvaluation())])]);

    Cache::put(GenerateArticleShortlistJob::CACHE_KEY, ["run_id" => "test-run", "status" => "queued"]);
    (new GenerateArticleShortlistJob("test-run"))->handle(new ShortlistScrapedArticlesWithLaya);
    $response = (new \App\Http\Controllers\Admin\ArticleShortlistController)();

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getData(true)["count"])->toBe(3)
        ->and(array_keys($response->getData(true)["data"][0]))->toBe(["id", "title", "url", "excerpt"])
        ->and(ScrapedArticle::query()->whereNotNull("processing_by")->count())->toBe(0);
    Bus::assertNotDispatched(TranslateScrapedArticleJob::class);

    $route = app("router")->getRoutes()->getByName("api.blog.article-shortlist");
    expect($route->gatherMiddleware())->toContain("auth", "role:" . \App\Models\Role::SYSTEM_ADMIN);
});

it("exige autenticação para testar a pré-seleção", function () {
    $this->getJson("/api/blog/article-shortlist")->assertUnauthorized();
    $this->postJson("/api/blog/article-shortlist")->assertUnauthorized();
});

it("inicia a seleção em segundo plano sem chamar o Laya e reutiliza a tarefa ativa", function () {
    Bus::fake([GenerateArticleShortlistJob::class]);
    Http::fake();
    $controller = new \App\Http\Controllers\Admin\ArticleShortlistController;

    expect($controller()->getData(true)["status"])->toBe("idle");
    $started = $controller->store();
    $again = $controller->store();
    expect($started->getStatusCode())->toBe(202)
        ->and($started->getData(true)["status"])->toBe("queued")
        ->and($again->getData(true)["run_id"])->toBe($started->getData(true)["run_id"])
        ->and($controller()->getData(true)["status"])->toBe("queued");
    Bus::assertDispatchedTimes(GenerateArticleShortlistJob::class, 1);
    Bus::assertDispatched(GenerateArticleShortlistJob::class, fn ($job) => $job->connection === "laya"
        && $job->queue === "article-shortlists"
        && $job->timeout === 120 && $job->tries === 1 && $job->failOnTimeout
        && $job->timeout < config("queue.connections.laya.retry_after"));
    Http::assertNothingSent();
});

it("processa em lotes pequenos e publica o progresso antes de concluir", function () {
    prepareAssistantSelection();
    config(["assistant.laya.request_size" => 2]);
    Cache::put(GenerateArticleShortlistJob::CACHE_KEY, ["run_id" => "progress", "status" => "queued"]);
    $observed = [];
    Http::fake(function ($request) use (&$observed) {
        $observed[] = Cache::get(GenerateArticleShortlistJob::CACHE_KEY)["processed"];
        return Http::response(["results" => array_fill(0, count($request["states"]), layaEvaluation())]);
    });

    (new GenerateArticleShortlistJob("progress"))->handle(new ShortlistScrapedArticlesWithLaya);

    $state = Cache::get(GenerateArticleShortlistJob::CACHE_KEY);
    expect($observed)->toBe([0, 2])
        ->and($state["status"])->toBe("completed")
        ->and($state["processed"])->toBe(3)
        ->and($state["total"])->toBe(3)
        ->and($state["count"])->toBe(3);
});

it("expõe falhas e permite iniciar uma nova tentativa", function () {
    Bus::fake([GenerateArticleShortlistJob::class]);
    Cache::put(GenerateArticleShortlistJob::CACHE_KEY, ["run_id" => "failed-run", "status" => "running"]);
    (new GenerateArticleShortlistJob("failed-run"))->failed(new RuntimeException("timeout"));
    $controller = new \App\Http\Controllers\Admin\ArticleShortlistController;

    expect($controller()->getData(true)["status"])->toBe("failed")
        ->and($controller->store()->getData(true)["status"])->toBe("queued");
    Bus::assertDispatchedTimes(GenerateArticleShortlistJob::class, 1);
});

it("ignora tarefas antigas sem sobrescrever o estado da nova execução", function () {
    Cache::put(GenerateArticleShortlistJob::CACHE_KEY, ["run_id" => "new-run", "status" => "queued"]);
    Http::fake();
    $old = new GenerateArticleShortlistJob("old-run");
    $old->handle(new ShortlistScrapedArticlesWithLaya);
    $old->failed(new RuntimeException("timeout"));
    expect(Cache::get(GenerateArticleShortlistJob::CACHE_KEY)["status"])->toBe("queued");
    Http::assertNothingSent();
});

it("sinaliza tarefas expiradas sem deixar a tela esperando indefinidamente", function () {
    Cache::put(GenerateArticleShortlistJob::CACHE_KEY, [
        "run_id" => "expired", "status" => "running", "expires_at" => now()->subMinute()->timestamp,
    ]);
    $controller = new \App\Http\Controllers\Admin\ArticleShortlistController;
    expect($controller()->getData(true)["status"])->toBe("failed");
});
