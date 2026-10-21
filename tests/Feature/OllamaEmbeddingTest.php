<?php

use App\Http\Controllers\Admin\OllamaEmbeddingController;
use App\Http\Requests\OllamaEmbedding\UpdateOllamaEmbedModelRequest;
use App\Models\Setting;
use App\Support\OllamaEmbeddingConfig;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    config([
        "database.default" => "sqlite",
        "database.connections.sqlite.database" => ":memory:",
        "services.ollama.base_url" => "http://ollama.test/api",
        "services.ollama.timeout" => 120,
    ]);

    DB::purge("sqlite");
    Cache::flush();

    Schema::create("settings", function (Blueprint $table) {
        $table->string("key")->primary();
        $table->text("value");
        $table->timestamps();
    });

    foreach( ["post_chunks", "search_embedding_caches"] as $table ) {
        Schema::create($table, function (Blueprint $table) {
            $table->id();
            $table->text("embedding");
        });

        DB::table($table)->insert(["embedding" => "[1,2,3]"]);
    }

    Setting::setValue(OllamaEmbeddingConfig::KEY_EMBED_MODEL, "old-model");
    Setting::setValue(OllamaEmbeddingConfig::KEY_EMBED_DIMENSION, "3");
    OllamaEmbeddingConfig::model();
    OllamaEmbeddingConfig::dimension();

    $this->request = Mockery::mock(UpdateOllamaEmbedModelRequest::class);
    $this->request->shouldReceive("validated")->with("embed_model")->andReturn("new-model");
    $this->database = Mockery::mock(DB::getFacadeRoot())->makePartial();
    DB::swap($this->database);
});

it("testa o modelo e atualiza as dimensões das duas tabelas", function () {
    Http::fake([
        "*/tags" => Http::response(["models" => [["name" => "new-model"]]]),
        "*/embed" => Http::response(["embeddings" => [[1, 2]]]),
    ]);

    // O SQLite valida a transação; os comandos específicos do pgvector são interceptados.
    $database = $this->database;
    $database->shouldReceive("statement")->once()->with("DROP INDEX IF EXISTS post_chunks_embedding_idx")->andReturn(true);
    $database->shouldReceive("statement")->once()->with("ALTER TABLE post_chunks ALTER COLUMN embedding TYPE vector(2)")->andReturn(true);
    $database->shouldReceive("statement")->once()->with("ALTER TABLE search_embedding_caches ALTER COLUMN embedding TYPE vector(2)")->andReturn(true);
    $database->shouldReceive("statement")->once()->with("CREATE INDEX post_chunks_embedding_idx ON post_chunks USING hnsw (embedding vector_cosine_ops) WHERE embedding IS NOT NULL")->andReturn(true);

    $response = (new OllamaEmbeddingController)->update($this->request);

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getData(true)["embed_dimension"])->toBe(2)
        ->and(OllamaEmbeddingConfig::model())->toBe("new-model")
        ->and(OllamaEmbeddingConfig::dimension())->toBe(2)
        ->and(DB::table("post_chunks")->count())->toBe(0)
        ->and(DB::table("search_embedding_caches")->count())->toBe(0);

    Http::assertSent(fn ($request) => str_ends_with($request->url(), "/embed")
        && $request["model"] === "new-model"
        && !empty($request["input"]));
});

it("troca o modelo sem alterar as colunas quando a dimensão é igual", function () {
    Http::fake([
        "*/tags" => Http::response(["models" => [["name" => "new-model"]]]),
        "*/embed" => Http::response(["embeddings" => [[1, 2, 3]]]),
    ]);

    $this->database->shouldNotReceive("statement");

    $response = (new OllamaEmbeddingController)->update($this->request);

    expect($response->getStatusCode())->toBe(200)
        ->and(OllamaEmbeddingConfig::model())->toBe("new-model")
        ->and(OllamaEmbeddingConfig::dimension())->toBe(3)
        ->and(DB::table("post_chunks")->count())->toBe(0);
});

it("preserva os dados e a configuração quando o teste do modelo falha", function ($payload, $status, $expected_status) {
    Http::fake([
        "*/tags" => Http::response(["models" => [["name" => "new-model"]]]),
        "*/embed" => Http::response($payload, $status),
    ]);

    $response = (new OllamaEmbeddingController)->update($this->request);

    expect($response->getStatusCode())->toBe($expected_status)
        ->and(OllamaEmbeddingConfig::model())->toBe("old-model")
        ->and(OllamaEmbeddingConfig::dimension())->toBe(3)
        ->and(DB::table("post_chunks")->count())->toBe(1)
        ->and(DB::table("search_embedding_caches")->count())->toBe(1);
})->with([
    "vetor vazio" => [["embeddings" => [[]]], 200, 422],
    "vetor ausente" => [[], 200, 422],
    "erro do Ollama" => [["error" => "Modelo incompatível."], 400, 502],
]);

it("desfaz a limpeza quando a alteração da coluna falha", function () {
    Http::fake([
        "*/tags" => Http::response(["models" => [["name" => "new-model"]]]),
        "*/embed" => Http::response(["embeddings" => [[1, 2]]]),
    ]);

    $this->database->shouldReceive("statement")->once()
        ->with("DROP INDEX IF EXISTS post_chunks_embedding_idx")
        ->andThrow(new RuntimeException("Falha ao alterar o banco."));

    $response = (new OllamaEmbeddingController)->update($this->request);

    expect($response->getStatusCode())->toBe(500)
        ->and(OllamaEmbeddingConfig::model())->toBe("old-model")
        ->and(OllamaEmbeddingConfig::dimension())->toBe(3)
        ->and(DB::table("post_chunks")->count())->toBe(1)
        ->and(DB::table("search_embedding_caches")->count())->toBe(1);
});
