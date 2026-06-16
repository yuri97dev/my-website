<?php

namespace App\Helpers;

class TextFormatter
{
    public static function all(string | null $text): string
    {
        if( empty($text) ) return (string) $text;

        $without_html = self::removeHTML($text);
        $without_diacritics = self::removeDiacritics($without_html);
        $with_single_spaces = self::removeExtraSpaces($without_diacritics);

        return mb_strtoupper($with_single_spaces);
    }

    public static function removeHTML(string | null $text): string
    {
        if( empty($text) ) return (string) $text;

        $without_html = strip_tags($text);
        $without_html_entities = html_entity_decode($without_html);

        return trim($without_html_entities);
    }

    public static function removeExtraSpaces(string | null $text): string
    {
        if( empty($text) ) return (string) $text;

        $text_with_spaces_for_tabs = str_replace("\t", " ", $text);
        $text_with_single_spaces = preg_replace("/ {2,}/", " ", $text_with_spaces_for_tabs);

        return trim($text_with_single_spaces);
    }

    public static function removeDiacritics(string | null $text): string
    {
        if( empty($text) ) return (string) $text;

        $diacritics = [
            "à"=>"a", "á"=>"a", "â"=>"a", "ã"=>"a", "ä"=>"a", "å"=>"a", "æ"=>"a",
            "è"=>"e", "é"=>"e", "ê"=>"e", "ë"=>"e",
            "ì"=>"i", "í"=>"i", "î"=>"i", "ï"=>"i",
            "ð"=>"o", "ò"=>"o", "ó"=>"o", "ô"=>"o", "õ"=>"o", "ö"=>"o", "ø"=>"o",
            "ù"=>"u", "ú"=>"u", "û"=>"u", "Ü"=>"u",
            "ý"=>"y", "ÿ"=>"y",
            "þ"=>"b", "ç"=>"c", "Ð"=>"d",
            "š"=>"s", "ñ"=>"n", "ž"=>"z",
        ];

        foreach($diacritics as $diacritic => $letter)
        {
            $text = str_replace($diacritic, $letter, $text);

            $text = str_replace(
                mb_strtoupper($diacritic),
                mb_strtoupper($letter),
                $text
            );
        }

        return $text;
    }

    public static function removeStopWords(string | null $text): string
    {
        if( empty($text) ) return (string) $text;

        $stop_words = array_flip([
            "o", "a", "os", "as", "um", "uma", "uns", "umas",
            "de", "do", "da", "dos", "das", "em", "no", "na", "nos", "nas",
            "por", "pelo", "pela", "pelos", "pelas", "para", "com", "sem",
            "e", "ou", "mas", "nem", "que", "se", "como",
            "eu", "tu", "ele", "ela", "eles", "elas", "me", "te", "lhe", "nos", "vos",
            "nao", "sim",
        ]);

        $keywords = explode(" ", $text);

        $filtered_keywords = array_filter(
            $keywords,
            function($keyword) use ($stop_words) {
                $keyword = mb_strtolower($keyword);
                return !isset($stop_words[$keyword]);
            });

        return implode(" ", $filtered_keywords);
    }
}