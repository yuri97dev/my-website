<?php

namespace App\Helpers;

use Masterminds\HTML5;
use Symfony\Component\DomCrawler\UriResolver;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use League\HTMLToMarkdown\HtmlConverter;
use League\HTMLToMarkdown\Converter\TableConverter;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use tidy;

class HtmlFormatter
{
    public static function all(string $content, ?string $base_url = null): string
    {
        $content = self::resolveLazyLoadedImages($content);

        $sanitized_content = self::sanitize($content);
        $markdown_content = self::convertToMarkdown($sanitized_content);
        $html_content = self::convertFromMarkdown($markdown_content);

        if( !empty($base_url) ) {
            $html_content = self::absolutizeUrls($html_content, $base_url);
        }

        $beautified_content = self::beautify($html_content);

        return $beautified_content;
    }

    public static function sanitize(string $content): string
    {
        $config = (new HtmlSanitizerConfig())
            ->withMaxInputLength(-1)
            ->allowStaticElements()
            ->allowRelativeLinks()
            ->allowRelativeMedias()
            ->dropElement("script")
            ->dropElement("noscript")
            ->dropElement("iframe")
            ->dropElement("object")
            ->dropElement("embed")
            ->dropElement("form");

        $sanitizer = new HtmlSanitizer($config);

        return $sanitizer->sanitize($content);
    }

    public static function convertToMarkdown(string $content): string
    {
        $converter = new HtmlConverter([ "strip_tags" => true ]);
        $converter->getEnvironment()->addConverter(new TableConverter());

        return $converter->convert($content);
    }

    public static function convertFromMarkdown(string $content): string
    {
        $converter = new GithubFlavoredMarkdownConverter([
            "html_input" => "strip",
            "allow_unsafe_links" => false,
        ]);

        return $converter->convert($content);
    }

    public static function beautify(string $content): string
    {
        $tidy_config = [
            "indent" => true,
            "indent-spaces" => 4,
            "wrap" => 200,
            "output-xhtml" => false,
            "show-body-only" => true,
        ];

        $tidy = new tidy();

        $tidy->parseString($content, $tidy_config, "utf8");
        $tidy->cleanRepair();

        return (string) $tidy;
    }

    public static function resolveLazyLoadedImages(string $html): string
    {
        if( empty($html) ) return $html;

        $html5 = new HTML5();
        $fragment = $html5->loadHTMLFragment($html);

        $walk = function($node) use (&$walk) {
            if(
                $node instanceof \DOMElement &&
                $node->tagName === "img" &&
                $node->hasAttribute("data-src")
            ) {
                $src = trim($node->getAttribute("data-src"));

                if( !empty($src) ) {
                    $node->setAttribute("src", $src);
                    $node->removeAttribute("data-src");
                }
            }

            foreach($node->childNodes as $child) {
                $walk($child);
            }
        };

        foreach($fragment->childNodes as $child) {
            $walk($child);
        }

        return $html5->saveHTML($fragment);
    }

    public static function absolutizeUrls(string $html, string $base_url): string
    {
        if( empty($html) ) return $html;

        $html5 = new HTML5();
        $fragment = $html5->loadHTMLFragment($html);

        $url_attributes = [ "href", "src" ];

        $walk = function($node) use (&$walk, $base_url, $url_attributes) {
            if( $node instanceof \DOMElement ) {
                foreach($url_attributes as $attribute) {
                    if( !$node->hasAttribute($attribute) ) continue;

                    $url = $node->getAttribute($attribute);

                    if( empty($url) ) continue;

                    $node->setAttribute($attribute, UriResolver::resolve($url, $base_url));
                }
            }

            foreach($node->childNodes as $child) {
                $walk($child);
            }
        };

        foreach($fragment->childNodes as $child) {
            $walk($child);
        }

        return $html5->saveHTML($fragment);
    }
}
