<?php

namespace App\Jobs;

use App\Helpers\Ollama;
use App\Models\SearchEmbeddingCache;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateSearchEmbeddingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $search,
        protected string $normalized_query,
    ) {
        $this->onQueue("search-embeddings");
    }

    public function handle(): void
    {
        $embed_model = config("services.ollama.embed_model");

        $exists = SearchEmbeddingCache::query()
            ->where("normalized_query", $this->normalized_query)
            ->where("embedding_model", $embed_model)
            ->exists();

        if( $exists ) {
            return;
        }

        try {
            $ollama_response = Ollama::embed($this->search);

            if( empty($ollama_response["embeddings"]) ) {
                return;
            }

            $embedding = json_encode($ollama_response["embeddings"][0]);

            SearchEmbeddingCache::updateOrCreate(
                [
                    "normalized_query" => $this->normalized_query,
                    "embedding_model" => $embed_model,
                ],
                [
                    "embedding" => $embedding,
                ]
            );
        } catch(Exception $e) {
            report($e);
        }
    }
}
