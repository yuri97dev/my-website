<?php

namespace App\Actions;

use App\Models\ScrapedArticle;
use League\HTMLToMarkdown\HtmlConverter;
use League\HTMLToMarkdown\Converter\TableConverter;
use Droath\TextChunker\Facades\TextChunker;
use Illuminate\Support\Facades\Log;
use App\Helpers\Ollama;

class TranslateScrapedArticles
{
    private array $link_map = [];
    private array $iframe_map = [];

    public function __invoke(): void
    {
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
        
        // Log::info($markdown_content . "\n\n\n\n");

        Log::info($markdown_with_placeholders);

        $chunks = TextChunker::strategy("sentence")
            ->size(2)
            ->chunk($markdown_with_placeholders);

        $params = [];

        $prompt_file = storage_path("app/private/prompts/translation.txt");

        if( file_exists($prompt_file) ) {
            $params["system"] = file_get_contents($prompt_file);
        }

        $translated_text = "";

        foreach($chunks as $index => $chunk) {
            Log::info("CHUNK {$index}");

            $ollama_response = Ollama::generate($chunk->text, $params);
            $translated_chunk = $ollama_response["response"];

            $translated_text .= $translated_chunk;
        }

        Log::info($translated_text);
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