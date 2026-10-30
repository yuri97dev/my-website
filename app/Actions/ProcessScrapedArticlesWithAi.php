<?php

namespace App\Actions;

use App\Helpers\Ollama;
use App\Helpers\TextFormatter;
use App\Jobs\TranslateScrapedArticleJob;
use App\Models\AiPrompt;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\PostTag;
use App\Models\ScrapedArticle;
use App\Models\User;
use App\Support\AssistantPrompts;
use Droath\TextChunker\Facades\TextChunker;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ProcessScrapedArticlesWithAi
{
    private const POOL_SIZE = 50;

    private const CHUNK_SIZE = 5;

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

        if( $this->structurePendingDraft() ) {
            return;
        }

        $this->chooseAndTranslate();
    }

    private function releaseStaleLocks(): void
    {
        ScrapedArticle::query()
            ->where("processing_by", User::ASSISTANT_ID)
            ->where("processing_at", "<", now()->subMinutes((int) config("assistant.lock_ttl_minutes")))
            ->whereNull("post_id")
            ->update([
                "processing_by" => null,
                "processing_at" => null,
            ]);
    }

    private function structurePendingDraft(): bool
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
            $prompt = AssistantPrompts::get(AssistantPrompts::KEY_STRUCTURE_SYSTEM);

            if( !$prompt ) {
                Log::error("ProcessScrapedArticlesWithAi: prompt structure_system não encontrado.");
                return false;
            }

            $system = str_replace(
                ["{categories}", "{tags}"],
                [$this->topNames("category"), $this->topNames("tag")],
                $prompt->content,
            );

            $content = trim($article->translated_content ?? "");
            $title = mb_substr(trim($article->translated_title ?? ""), 0, 200);

            if( $title === "" || $content === "" ) {
                Log::error("ProcessScrapedArticlesWithAi: título ou conteúdo vazio ao estruturar artigo {$article->id}.");
                return false;
            }

            $response = Ollama::chat($system, implode("\n\n", [
                "Título: {$title}",
                "Texto (contexto):\n" . TextFormatter::removeHTML($content),
            ]), [
                "model" => $prompt->model,
            ]);

            $data = $this->parseJson($response);

            if( $data === null ) {
                Log::error("ProcessScrapedArticlesWithAi: resposta inválida ao estruturar artigo {$article->id}.");
                return false;
            }

            $category_name = trim((string) ($data["category"] ?? ""));

            if( $category_name === "" ) {
                Log::error("ProcessScrapedArticlesWithAi: resposta inválida ao estruturar artigo {$article->id}.");
                return false;
            }

            $category = PostCategory::query()->firstOrCreate(
                ["slug" => Str::slug($category_name)],
                ["name" => $category_name],
            );

            $post = Post::create([
                "author_id" => config("assistant.author_id"),
                "category_id" => $category->id,
                "slug" => $this->uniqueSlug(Str::slug($title)),
                "title" => $title,
                "status" => Post::STATUS_DRAFT,
                "excerpt" => mb_substr(trim($data["excerpt"] ?? TextFormatter::removeHTML($content)), 0, 500),
                "content" => $content,
                "meta_description" => mb_substr(trim($data["meta_description"] ?? ""), 0, 160) ?: null,
                "thumbnail_path" => $this->thumbnailPath($content),
            ]);

            $tag_ids = $this->tagIds($data["tags"] ?? []);

            if( $tag_ids !== [] ) {
                $post->tags()->sync($tag_ids);
            }

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

    private function chooseAndTranslate(): void
    {
        $waiting_translation = ScrapedArticle::query()
            ->where("processing_by", User::ASSISTANT_ID)
            ->whereNull("translated_content")
            ->whereNull("post_id")
            ->exists();

        if( $waiting_translation ) {
            return;
        }

        $prompt = AssistantPrompts::get(AssistantPrompts::KEY_SELECT_SYSTEM);

        if( !$prompt ) {
            Log::error("ProcessScrapedArticlesWithAi: prompt select_system não encontrado.");
            return;
        }

        $articles = ScrapedArticle::query()
            ->eligibleForAssistant()
            ->orderByDesc("id")
            ->limit(self::POOL_SIZE)
            ->get();

        if( $articles->isEmpty() ) {
            return;
        }

        try {
            $article = $this->tournament($articles, $prompt);
        } catch(Throwable $e) {
            Log::error("ProcessScrapedArticlesWithAi: falha ao escolher artigo: {$e->getMessage()}");
            return;
        }

        if( !$article ) {
            return;
        }

        $article->update([
            "processing_by" => User::ASSISTANT_ID,
            "processing_at" => now(),
        ]);

        Cache::add(
            TranslateScrapedArticleJob::cacheLockKey($article->id),
            true,
            TranslateScrapedArticleJob::CACHE_LOCK_TTL,
        );

        TranslateScrapedArticleJob::dispatch($article->id);

        Log::info("ProcessScrapedArticlesWithAi: artigo {$article->id} escolhido para tradução.");
    }

    private function tournament(Collection $articles, AiPrompt $prompt): ?ScrapedArticle
    {
        $round = $articles->values();

        while( $round->count() > 1 ) {
            $winners = collect();

            foreach( $round->chunk(self::CHUNK_SIZE) as $chunk ) {
                $chunk = $chunk->values();

                if( $chunk->count() === 1 ) {
                    $winners->push($chunk->first());
                    continue;
                }

                $winner = $this->chooseOne($chunk, $prompt);

                if( !$winner ) {
                    return null;
                }

                $winners->push($winner);
            }

            $round = $winners->values();
        }

        return $round->first();
    }

    private function chooseOne(Collection $chunk, AiPrompt $prompt): ?ScrapedArticle
    {
        $lines = $chunk->map(function (ScrapedArticle $article, int $index) {
            $number = $index + 1;

            return "{$number}. Title: {$article->title}\nSummary: {$this->sentences($article->content)}";
        })->implode("\n\n");

        $response = Ollama::chat($prompt->content, $lines, [
            "model" => $prompt->model,
        ]);

        $choice = $this->parseChoice($response, $chunk->count());

        if( $choice === null ) {
            Log::error("ProcessScrapedArticlesWithAi: resposta inválida ao escolher artigo: {$response}");
            return null;
        }

        return $chunk->get($choice - 1);
    }

    private function sentences(string $content): string
    {
        $plain = trim(TextFormatter::removeHTML($content));

        if( $plain === "" ) {
            return "";
        }

        $chunks = TextChunker::strategy("sentence")
            ->size(3)
            ->chunk($plain);

        return isset($chunks[0]) ? $chunks[0]->text : "";
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

    private function tagIds(mixed $tags): array
    {
        if( is_string($tags) ) {
            $tags = array_map("trim", explode(",", $tags));
        }

        if( !is_array($tags) ) {
            return [];
        }

        $ids = [];

        foreach( $tags as $tag_name ) {
            $tag_name = trim((string) $tag_name);

            if( $tag_name === "" ) {
                continue;
            }

            $ids[] = PostTag::query()->firstOrCreate(
                ["slug" => Str::slug($tag_name)],
                ["name" => $tag_name],
            )->id;
        }

        return array_values(array_unique($ids));
    }

    private function topNames(string $kind): string
    {
        if( $kind === "category" ) {
            $counts = Post::query()
                ->published()
                ->select(["category_id", DB::raw("count(*) as posts_count")])
                ->whereNotNull("category_id")
                ->groupBy("category_id");

            $names = PostCategory::query()
                ->joinSub($counts, "post_counts", "post_counts.category_id", "post_categories.id")
                ->orderByDesc("post_counts.posts_count")
                ->limit(20)
                ->pluck("post_categories.name");
        } else {
            $counts = Post::query()
                ->published()
                ->join("post_tag", "posts.id", "=", "post_tag.post_id")
                ->select(["post_tag.post_tag_id", DB::raw("count(*) as posts_count")])
                ->groupBy("post_tag.post_tag_id");

            $names = PostTag::query()
                ->joinSub($counts, "post_counts", "post_counts.post_tag_id", "post_tags.id")
                ->orderByDesc("post_counts.posts_count")
                ->limit(20)
                ->pluck("post_tags.name");
        }

        return $names->implode(", ") ?: "nenhuma";
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
        if( !preg_match("/\b([1-9]|10)\b/", $response, $matches) ) {
            return null;
        }

        $choice = (int) $matches[1];

        return $choice >= 1 && $choice <= $max ? $choice : null;
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
