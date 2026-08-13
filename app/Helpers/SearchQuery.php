<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Builder;

class SearchQuery
{
    private const TRGM_THRESHOLD = 0.3;

    private const SHORT_WORD_MAX_LENGTH = 3;

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
