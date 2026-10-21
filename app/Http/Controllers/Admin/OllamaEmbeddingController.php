<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Ollama;
use App\Http\Controllers\Controller;
use App\Http\Requests\OllamaEmbedding\PullOllamaModelRequest;
use App\Http\Requests\OllamaEmbedding\UpdateOllamaEmbedModelRequest;
use App\Models\PostChunk;
use App\Models\SearchEmbeddingCache;
use App\Support\OllamaEmbeddingConfig;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class OllamaEmbeddingController extends Controller
{
    public function show(): JsonResponse
    {
        $ollama_error = null;
        $models = [];

        try {
            $tags = Ollama::tags();

            $models = collect($tags["models"] ?? [])
                ->map(fn (array $model) => [
                    "name" => $model["name"],
                    "size" => $model["size"] ?? null,
                    "modified_at" => $model["modified_at"] ?? null,
                ])
                ->sortBy("name")
                ->values()
                ->all();
        } catch (Throwable) {
            $ollama_error = "Não foi possível conectar ao Ollama.";
        }

        return response()->json([
            "embed_model" => OllamaEmbeddingConfig::model(),
            "embed_dimension" => OllamaEmbeddingConfig::dimension(),
            "post_chunks_count" => PostChunk::count(),
            "search_embedding_caches_count" => SearchEmbeddingCache::count(),
            "models" => $models,
            "ollama_error" => $ollama_error,
        ], 200);
    }

    public function pull(PullOllamaModelRequest $request): JsonResponse
    {
        $model = $request->validated("model");

        set_time_limit(0);

        try {
            Ollama::pull($model);
        } catch (Throwable $exception) {
            return response()->json([
                "message" => self::ollamaErrorMessage(
                    $exception,
                    "Não foi possível baixar o modelo no Ollama.",
                ),
            ], 502);
        }

        return response()->json([
            "message" => "Modelo baixado com sucesso.",
            "model" => $model,
        ], 200);
    }

    public function update(UpdateOllamaEmbedModelRequest $request): JsonResponse
    {
        $embed_model = $request->validated("embed_model");

        try {
            $installed = collect(Ollama::tags()["models"] ?? [])
                ->pluck("name")
                ->all();

            if( !in_array($embed_model, $installed, true) ) return response()->json([
                "message" => "O modelo não está instalado no Ollama. Baixe-o antes de aplicar.",
            ], 422);
        } catch (Throwable) {
            return response()->json([
                "message" => "Não foi possível verificar os modelos no Ollama.",
            ], 502);
        }

        try {
            $response = Ollama::embed("Teste de dimensão de embedding.", $embed_model);
            $embedding = $response["embeddings"][0] ?? null;

            if( !is_array($embedding) || count($embedding) === 0 ) {
                return response()->json([
                    "message" => "O modelo não retornou um embedding válido.",
                ], 422);
            }

            $embed_dimension = count($embedding);
        } catch (Throwable $exception) {
            return response()->json([
                "message" => self::ollamaErrorMessage(
                    $exception,
                    "Não foi possível testar o modelo de embedding.",
                ),
            ], 502);
        }

        $dimension_changed = $embed_dimension !== OllamaEmbeddingConfig::dimension();

        if( $embed_model === OllamaEmbeddingConfig::model() && !$dimension_changed ) {
            return response()->json([
                "message" => "Este já é o modelo de embedding em uso.",
                "embed_model" => $embed_model,
                "embed_dimension" => $embed_dimension,
                "post_chunks_count" => PostChunk::count(),
                "search_embedding_caches_count" => SearchEmbeddingCache::count(),
            ], 200);
        }

        try {
            DB::transaction(function () use ($embed_model, $embed_dimension, $dimension_changed) {
                PostChunk::query()->delete();
                SearchEmbeddingCache::query()->delete();

                if( $dimension_changed ) {
                    DB::statement("DROP INDEX IF EXISTS post_chunks_embedding_idx");
                    DB::statement("ALTER TABLE post_chunks ALTER COLUMN embedding TYPE vector(" . $embed_dimension . ")");
                    DB::statement("ALTER TABLE search_embedding_caches ALTER COLUMN embedding TYPE vector(" . $embed_dimension . ")");
                    DB::statement("CREATE INDEX post_chunks_embedding_idx ON post_chunks USING hnsw (embedding vector_cosine_ops) WHERE embedding IS NOT NULL");
                }

                OllamaEmbeddingConfig::setModel($embed_model);
                OllamaEmbeddingConfig::setDimension($embed_dimension);
            });
        } catch (Throwable) {
            return response()->json([
                "message" => "Não foi possível atualizar o modelo de embedding.",
            ], 500);
        }

        return response()->json([
            "message" => "Modelo de embedding atualizado. Trechos e caches semânticos foram limpos.",
            "embed_model" => $embed_model,
            "embed_dimension" => $embed_dimension,
            "post_chunks_count" => 0,
            "search_embedding_caches_count" => 0,
        ], 200);
    }

    private static function ollamaErrorMessage(Throwable $exception, string $fallback): string
    {
        if( !$exception instanceof RequestException || !$exception->response ) {
            return $fallback;
        }

        $error = $exception->response->json("error");

        if( !is_string($error) || trim($error) === "" ) {
            return $fallback;
        }

        return trim($error);
    }
}
