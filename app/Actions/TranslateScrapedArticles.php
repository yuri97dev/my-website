<?php

namespace App\Actions;

use Symfony\AI\Platform\Bridge\DeepSeek\Factory as DeepSeekFactory;
use Symfony\AI\Platform\Bridge\Gemini\Factory as GeminiFactory;
use Symfony\AI\Platform\Bridge\OpenAi\Factory as OpenAiFactory;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use App\Models\AiPrompt;
use App\Models\ScrapedArticle;
use Exception;

class TranslateScrapedArticles
{
    public function __invoke(): void
    {
        $ai_prompt = AiPrompt::query()
            ->key("post-translation")
            ->whereHas("aiProvider", fn ($query) => $query->enabled())
            ->with("aiProvider")
            ->first();

        if( !$ai_prompt ) {
            return;
        }

        $api_key = $ai_prompt->aiProvider->api_key;
        $provider = $ai_prompt->aiProvider->provider;

        $platform = match($provider) {
            "gemini" => GeminiFactory::createPlatform($api_key),
            "deepseek" => DeepSeekFactory::createPlatform($api_key),
            "openai" => OpenAiFactory::createPlatform($api_key),
            default => throw new Exception("Provedor não suportado"),
        };

        $article = ScrapedArticle::query()
            ->enabled()
            ->whereNull("translation_rejected_at")
            ->whereNull("translated_content")
            ->orderBy("id")
            ->first();

        if( !$article ) {
            return;
        }

        $content_to_translate = "<h1>{$article->title}</h1>\n\n{$article->content}";

        $messages = new MessageBag(
            Message::forSystem($ai_prompt->content),
            Message::ofUser($content_to_translate)
        );

        $result = $platform->invoke($ai_prompt->model, $messages);

        $translated_content = $result->asText();

        [$translated_title, $translated_content] = explode("</h1>", $translated_content, 2);

        $translated_title = ltrim($translated_title, "<h1>");
        $translated_title = trim($translated_title);

        $article->translated_title = $translated_title;
        $article->translated_content = $translated_content;

        $article->save();
    }
}
