<?php

namespace App\Actions;

use Symfony\AI\Platform\Bridge\DeepSeek\Factory as DeepSeekFactory;
use Symfony\AI\Platform\Bridge\Gemini\Factory as GeminiFactory;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use League\HTMLToMarkdown\HtmlConverter;
use League\HTMLToMarkdown\Converter\TableConverter;
use Illuminate\Support\Facades\Log;
use App\Models\AiPrompt;
use App\Models\ScrapedArticle;
use Exception;

class TranslateScrapedArticles
{
    private array $link_map = [];
    private array $iframe_map = [];

    public function __invoke(): void
    {
        $ai_prompt = AiPrompt::query()
            ->key("post-translation")
            ->with("aiProvider")
            ->first();

        if( !$ai_prompt || !$ai_prompt->aiProvider->is_enabled ) {
            return;
        }

        $api_key = $ai_prompt->aiProvider->api_key;
        $provider = "deepseek";

        $platform = match($provider) {
            "gemini" => GeminiFactory::createPlatform($api_key),
            "deepseek" => DeepSeekFactory::createPlatform($api_key),
            default => throw new Exception("Provedor não suportado"),
        };

        $article = ScrapedArticle::query()
            ->enabled()
            ->whereNull("translated_content")
            ->where("content", "like", "%<iframe%") // for testing
            ->skip(1) // for testing
            ->orderBy("id")
            ->first();

        if( !$article ) {
            return;
        }

        $content = $article->content;

        $converter = new HtmlConverter();
        $converter->getEnvironment()->addConverter(new TableConverter());

        $markdown_content = $converter->convert($content);

        $markdown_with_placeholders = $this->replaceLinksWithPlaceholders($markdown_content);
        $markdown_with_placeholders = $this->replaceIframesWithPlaceholders($markdown_with_placeholders);

        $messages = new MessageBag(
            Message::forSystem($ai_prompt->content),
            Message::ofUser($markdown_with_placeholders)
        );

        $result = $platform->invoke("deepseek-v4-flash", $messages);

        Log::info($result->asText());
    }

    private function replaceLinksWithPlaceholders(string $markdown_content): string
    {
        $pieces = explode("](", $markdown_content);

        $final_content = [
            array_shift($pieces),
        ];

        foreach($pieces as $index => $piece) {
            $key = "LINK_{$index}";

            $sub_pieces = explode(")", $piece);

            $link = array_shift($sub_pieces);

            $valid_url = filter_var($link, FILTER_VALIDATE_URL) !== false;
            $is_internal_link = str_starts_with($link, "/blog/scraped-images");

            if( !$valid_url && !$is_internal_link ) {
                $final_content[] = $piece;
                continue;
            }

            $rest = implode(")", $sub_pieces);

            $final_content[] = "{$key}){$rest}";

            $this->link_map[$key] = $link;
        }

        return implode("](", $final_content);
    }

    private function replaceIframesWithPlaceholders(string $markdown_content): string
    {
        $pieces = explode("<iframe", $markdown_content);

        $final_content = [
            array_shift($pieces),
        ];

        foreach($pieces as $index => $piece) {
            $key = "IFRAME_{$index}";

            $sub_pieces = explode("</iframe>", $piece);

            $iframe_props = array_shift($sub_pieces);
            $iframe_props = str_replace("'", '"', $iframe_props);

            $src_pieces = explode("src=", $iframe_props);

            $link = explode('"', $src_pieces[1])[1];

            if( filter_var($link, FILTER_VALIDATE_URL) === false ) {
                $final_content[] = $piece;
                continue;
            }

            $rest = implode("", $sub_pieces);

            $final_content[] = "({$key}) {$rest}";

            $this->iframe_map[$key] = $link;
        }

        return implode("", $final_content);
    }
}