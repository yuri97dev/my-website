<?php

namespace App\Actions;

use App\Helpers\Ollama;
use App\Helpers\TextFormatter;
use App\Jobs\TranslateScrapedArticleJob;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\ScrapedArticle;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ProcessScrapedArticlesWithAi
{
    public function __invoke(): void
    {
        if( !config("assistant.enabled") ) {
            return;
        }

        if( !User::query()->whereKey(config("assistant.author_id"))->exists() ) {
            Log::warning("ProcessScrapedArticlesWithAi: usuário autor do ajudante não encontrado.");
            return;
        }

        $this->releaseStaleLocks();

        if( $this->structureDraftPost() ) {
            return;
        }

        $this->pickAndTranslate();
    }

    private function releaseStaleLocks(): void
    {
        $cutoff = now()->subMinutes((int) config("assistant.lock_ttl_minutes"));

        ScrapedArticle::query()
            ->where("processing_by", User::ASSISTANT_ID)
            ->where("processing_at", "<", $cutoff)
            ->whereNull("post_id")
            ->update([
                "processing_by" => null,
                "processing_at" => null,
            ]);
    }

    private function structureDraftPost(): bool
    {
        $article = ScrapedArticle::query()
            ->where("processing_by", User::ASSISTANT_ID)
            ->whereNotNull("translated_content")
            ->whereNull("post_id")
            ->orderBy("id")
            ->first();

        if( !$article ) {
            return false;
        }

        $this->structureDraftForArticle($article);

        return true;
    }

    public function structureDraftForArticle(ScrapedArticle $article): bool
    {
        try {
            $categories = PostCategory::query()
                ->orderBy("name")
                ->pluck("name")
                ->implode(", ");

            $system = str_replace(
                "{categories}",
                $categories !== "" ? $categories : "nenhuma",
                config("assistant.prompts.structure_system"),
            );

            $user = implode("\n\n", [
                "Título: {$article->translated_title}",
                "Conteúdo:\n{$article->translated_content}",
            ]);

            $response = Ollama::chat($system, $user);
            $data = $this->parseJson($response);

            if( !$data ) {
                Log::error("ProcessScrapedArticlesWithAi: JSON inválido ao estruturar artigo {$article->id}.");
                return false;
            }

            $title = mb_substr(trim($data["title"] ?? $article->translated_title ?? ""), 0, 200);

            if( $title === "" ) {
                Log::error("ProcessScrapedArticlesWithAi: título vazio ao estruturar artigo {$article->id}.");
                return false;
            }

            $category_name = trim($data["category"] ?? "");

            if( $category_name === "" ) {
                Log::error("ProcessScrapedArticlesWithAi: categoria vazia ao estruturar artigo {$article->id}.");
                return false;
            }

            $category = PostCategory::query()->firstOrCreate(
                ["slug" => Str::slug($category_name)],
                ["name" => $category_name],
            );

            $content = trim($data["content"] ?? $article->translated_content ?? "");

            if( $content === "" ) {
                Log::error("ProcessScrapedArticlesWithAi: conteúdo vazio ao estruturar artigo {$article->id}.");
                return false;
            }

            $slug = Str::slug(trim($data["slug"] ?? $title));

            if( $slug === "" ) {
                $slug = Str::slug($title);
            }

            $post = Post::create([
                "author_id" => config("assistant.author_id"),
                "category_id" => $category->id,
                "slug" => $this->uniqueSlug($slug),
                "title" => $title,
                "status" => Post::STATUS_DRAFT,
                "excerpt" => mb_substr(trim($data["excerpt"] ?? $this->excerptFromContent($content)), 0, 500),
                "content" => $content,
                "meta_description" => mb_substr(trim($data["meta_description"] ?? ""), 0, 160) ?: null,
                "thumbnail_path" => $this->thumbnailPath($content),
            ]);

            $article->update([
                "post_id" => $post->id,
                "processing_by" => null,
                "processing_at" => null,
            ]);

            Log::info("ProcessScrapedArticlesWithAi: rascunho {$post->id} criado a partir do artigo {$article->id}.");

            return true;
        } catch(Throwable $e) {
            Log::error("ProcessScrapedArticlesWithAi: falha ao estruturar artigo {$article->id}: {$e->getMessage()}");

            return false;
        }
    }

    private function pickAndTranslate(): void
    {
        $pending_translation = ScrapedArticle::query()
            ->where("processing_by", User::ASSISTANT_ID)
            ->whereNull("translated_content")
            ->whereNull("post_id")
            ->exists();

        if( $pending_translation ) {
            return;
        }

        $pool_size = (int) config("assistant.pool_size");
        $selection_size = (int) config("assistant.selection_size");

        $candidates = ScrapedArticle::query()
            ->eligibleForAssistant()
            ->orderByDesc("id")
            ->limit($pool_size)
            ->get(["id", "title", "content"]);

        if( $candidates->count() < $selection_size ) {
            return;
        }

        $batch = $candidates->shuffle()->take($selection_size)->values();

        $lines = $batch->map(function (ScrapedArticle $article, int $index) {
            $number = $index + 1;
            $excerpt = $this->excerptPreview($article->content);

            return "{$number}. Título: {$article->title}\n   Resumo: {$excerpt}";
        })->implode("\n\n");

        try {
            $response = Ollama::chat(config("assistant.prompts.select_system"), $lines);
            $choice = $this->parseChoice($response, $selection_size);

            if( $choice === null ) {
                Log::error("ProcessScrapedArticlesWithAi: resposta inválida ao escolher artigo: {$response}");
                return;
            }

            $article = $batch[$choice - 1];

            $update = [
                "processing_by" => User::ASSISTANT_ID,
                "processing_at" => now(),
            ];

            if( config("assistant.store_selection_prompt") ) {
                $update["assistant_selection_prompt"] = $this->buildSelectionPrompt($lines);
            }

            $article->update($update);

            $lock_key = TranslateScrapedArticleJob::cacheLockKey($article->id);
            Cache::add($lock_key, true, TranslateScrapedArticleJob::CACHE_LOCK_TTL);

            TranslateScrapedArticleJob::dispatch($article->id);

            Log::info("ProcessScrapedArticlesWithAi: artigo {$article->id} escolhido para tradução.");
        } catch(Throwable $e) {
            Log::error("ProcessScrapedArticlesWithAi: falha ao escolher artigo: {$e->getMessage()}");
        }
    }

    public static function releaseAssistantLock(ScrapedArticle $article): void
    {
        if( !$article->isProcessedByAssistant() ) {
            return;
        }

        $article->update([
            "processing_by" => null,
            "processing_at" => null,
        ]);
    }

    private function buildSelectionPrompt(string $candidates): string
    {
        return implode("\n\n", [
            "=== System ===",
            config("assistant.prompts.select_system"),
            "=== Candidates ===",
            $candidates,
        ]);
    }

    private function excerptPreview(string $content): string
    {
        return mb_substr(TextFormatter::removeHTML($content), 0, 100);
    }

    private function excerptFromContent(string $content): string
    {
        return mb_substr(TextFormatter::removeHTML($content), 0, 500);
    }

    private function thumbnailPath(string $content): ?string
    {
        if( !preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $content, $matches) ) {
            return null;
        }

        return $matches[1];
    }

    private function uniqueSlug(string $slug): string
    {
        $base = $slug;
        $suffix = 2;

        while( Post::query()->where("slug", $slug)->exists() ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function parseChoice(string $response, int $max): ?int
    {
        if( !preg_match("/\b([1-9]|10)\b/", trim($response), $matches) ) {
            return null;
        }

        $choice = (int) $matches[1];

        if( $choice < 1 || $choice > $max ) {
            return null;
        }

        return $choice;
    }

    private function parseJson(string $text): ?array
    {
        $text = trim($text);

        if( str_starts_with($text, "```") ) {
            $line_break = strpos($text, "\n");

            $text = $line_break === false ? "" : substr($text, $line_break + 1);
            $text = rtrim($text);

            if( str_ends_with($text, "```") ) {
                $text = substr($text, 0, -3);
            }

            $text = trim($text);
        }

        $decoded = json_decode($text, true);

        return is_array($decoded) ? $decoded : null;
    }
}
