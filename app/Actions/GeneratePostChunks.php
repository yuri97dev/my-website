<?php

namespace App\Actions;

use Illuminate\Support\Facades\Log;
use League\HTMLToMarkdown\HtmlConverter;
use League\HTMLToMarkdown\Converter\TableConverter;
use Droath\TextChunker\Facades\TextChunker;
use App\Models\Post;
use App\Models\PostChunk;
use App\Helpers\Ollama;

class GeneratePostChunks
{
    public function __invoke(): void
    {
        $post = Post::query()
            ->whereDoesntHave("chunks")
            ->orderBy("id")
            ->first();

        $is_production = app()->isProduction();

        if( !$post ) {
            if( !$is_production ) {
                Log::info("GeneratePostChunks: no posts to process");
            }

            return;
        }

        $converter = new HtmlConverter([ "strip_tags" => true ]);
        $converter->getEnvironment()->addConverter(new TableConverter());

        $content = $converter->convert($post->content);

        $chunks = TextChunker::strategy("sentence")
            ->size(6)
            ->overlap(2)
            ->chunk($content);

        if( empty($chunks) ) {
            if( !$is_production ) {
                Log::info("GeneratePostChunks: post {$post->id} has no content to chunk");
            }

            return;
        }

        foreach($chunks as $index => $chunk) {
            $response = Ollama::embed($chunk->text);

            PostChunk::create([
                "post_id" => $post->id,
                "chunk_index" => $index + 1,
                "content" => $chunk->text,
                "token_count" => $response["prompt_eval_count"],
                "embedding_model" => config("services.ollama.embed_model"),
                "embedding" => json_encode($response["embeddings"][0]),
            ]);
        }
    }
}
