<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class SearchQuery
{
    private const TRGM_THRESHOLD = 0.3;

    private const SHORT_WORD_MAX_LENGTH = 3;

    private const STEM_WORD_MIN_LENGTH = 3;

    private const STEM_WORD_MAX_LENGTH = 50;

    private const STEM_WORD_MAX_COUNT = 10;

    public static function words(string $search): array
    {
        $normalized = TextFormatter::all($search);

        if( $normalized === "" ) {
            return [];
        }

        return array_values(array_filter(
            preg_split("/\s+/", $normalized),
            fn (string $word) => $word !== ""
        ));
    }

    public static function language(string $search): string
    {
        $words = self::stemWords($search);

        if( empty($words) ) {
            return "simple";
        }

        $portuguese_score = self::stemScore($search, "portuguese", $words);
        $english_score = self::stemScore($search, "english", $words);

        if( $portuguese_score > $english_score ) {
            return "portuguese";
        }

        if( $english_score > $portuguese_score ) {
            return "english";
        }

        return "simple";
    }

    public static function normalize(string $search, ?string $language = null): string
    {
        $language ??= self::language($search);

        $normalized = DB::scalar(
            "SELECT websearch_to_tsquery(?, ?)::text",
            [$language, $search]
        );

        return $normalized ?: mb_strtolower(trim($search));
    }

    private static function stemWords(string $search): array
    {
        $normalized = mb_strtolower(trim($search));

        if( $normalized === "" ) {
            return [];
        }

        $words = array_values(array_filter(
            preg_split("/\s+/", $normalized),
            fn (string $word) => mb_strlen($word) >= self::STEM_WORD_MIN_LENGTH
                && mb_strlen($word) <= self::STEM_WORD_MAX_LENGTH
        ));

        return array_slice($words, 0, self::STEM_WORD_MAX_COUNT);
    }

    private static function stemScore(string $search, string $config, array $words): int
    {
        $tsquery = DB::scalar(
            "SELECT websearch_to_tsquery(?, ?)::text",
            [$config, $search]
        );

        if( !$tsquery ) {
            return 0;
        }

        preg_match_all("/'([^']+)'/", $tsquery, $matches);
        $tokens = $matches[1] ?? [];

        $score = 0;

        foreach( $words as $word ) {
            foreach( $tokens as $token ) {
                if( $token !== $word && str_starts_with($word, $token) ) {
                    $score++;
                    break;
                }
            }
        }

        return $score;
    }

    public static function applyWordSearch(Builder $query, string $expression, string $search): void
    {
        foreach(self::words($search) as $word) {
            $query->where(function (Builder $query) use ($expression, $word) {
                if( mb_strlen($word) <= self::SHORT_WORD_MAX_LENGTH ) {
                    $query->whereRaw("{$expression} LIKE ?", ["%".$word."%"]);
                } else {
                    $query->whereRaw(
                        "({$expression} LIKE ? OR word_similarity(?, {$expression}) > ?)",
                        ["%".$word."%", $word, self::TRGM_THRESHOLD]
                    );
                }
            });
        }
    }

    public static function applySubstringSearch(Builder $query, string $column, string $search): void
    {
        $normalized = TextFormatter::all($search);

        if( $normalized === "" ) {
            return;
        }

        if( mb_strlen($normalized) <= self::SHORT_WORD_MAX_LENGTH ) {
            $query->where($column, "like", "%".$normalized."%");
            return;
        }

        $query->where(function (Builder $query) use ($column, $normalized) {
            $query
                ->where($column, "like", "%".$normalized."%")
                ->orWhereRaw("word_similarity(?, {$column}) > ?", [
                    $normalized,
                    self::TRGM_THRESHOLD,
                ]);
        });
    }
}
