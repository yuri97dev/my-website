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
        $content = self::replaceIframesWithPlaceholders($content);

        $sanitized_content = self::sanitize($content);
        $markdown_content = self::convertToMarkdown($sanitized_content);
        $html_content = self::convertFromMarkdown($markdown_content);

        if( !empty($base_url) ) {
            $html_content = self::absolutizeUrls($html_content, $base_url);
        }

        $html_content = self::restoreIframesFromPlaceholders($html_content);

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
        return self::transformHtmlFragment($html, function($node) {
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
        });
    }

    public static function absolutizeUrls(string $html, string $base_url): string
    {
        $url_attributes = [ "href", "src" ];

        return self::transformHtmlFragment($html, function($node) use ($base_url, $url_attributes) {
            if( $node instanceof \DOMElement ) {
                foreach($url_attributes as $attribute) {
                    if( !$node->hasAttribute($attribute) ) continue;

                    $url = $node->getAttribute($attribute);

                    if( empty($url) ) continue;

                    $node->setAttribute($attribute, UriResolver::resolve($url, $base_url));
                }
            }
        });
    }

    public static function rewriteImageSources(string $html, callable $resolver): string
    {
        return self::transformHtmlFragment($html, function($node) use ($resolver) {
            if(
                $node instanceof \DOMElement &&
                $node->tagName === "img" &&
                $node->hasAttribute("src")
            ) {
                $src = trim($node->getAttribute("src"));

                if( !empty($src) ) {
                    $resolved = $resolver($src);

                    if( is_string($resolved) && $resolved !== "" ) {
                        $node->setAttribute("src", $resolved);
                    }
                }
            }
        });
    }

    private static function transformHtmlFragment(string $html, callable $visitor): string
    {
        if( empty($html) ) return $html;

        $html5 = new HTML5();
        $fragment = $html5->loadHTMLFragment($html);

        $walk = function($node) use (&$walk, $visitor) {
            $visitor($node);

            foreach($node->childNodes as $child) {
                $walk($child);
            }
        };

        foreach($fragment->childNodes as $child) {
            $walk($child);
        }

        return $html5->saveHTML($fragment);
    }

    private static function replaceIframesWithPlaceholders(string $html): string
    {
        $domains = [
            "youtube.com",
            "www.youtube.com",
            "youtube-nocookie.com",
            "www.youtube-nocookie.com",
            "vimeo.com",
            "www.vimeo.com",
            "player.vimeo.com",
            "www.player.vimeo.com",
        ];

        return self::transformHtmlFragment($html, function($node) use ($domains) {
            if(
                $node instanceof \DOMElement &&
                $node->tagName === "iframe"
            ) {
                $iframe_domain = parse_url($node->getAttribute("src"), PHP_URL_HOST);

                if( in_array($iframe_domain, $domains) ) {
                    $src = $node->getAttribute("src");
                    $tag_content = "[[IFRAME||{$src}||IFRAME]]";
                    $text = $node->ownerDocument->createTextNode($tag_content);
                    $node->parentNode->insertBefore($text, $node->nextSibling);
                    $node->parentNode->removeChild($node);
                }
            }
        });
    }

    private static function restoreIframesFromPlaceholders(string $html): string
    {
        $from = ["[[IFRAME||", "||IFRAME]]"];
        $to = ['<iframe allowfullscreen frameborder="0" src="', '"></iframe>'];

        return str_replace($from, $to, $html);
    }
}
