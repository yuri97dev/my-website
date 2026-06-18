<?php

namespace App\Helpers;

use Illuminate\Support\Arr;

class Meta
{
    private static array $data = [];

    public static function get(): array
    {
        $key = request()->route()?->getName() ?? "";

        if( empty($key) ) return [];

        $data = array_merge(config("meta"), self::$data);

        $meta = Arr::get($data, $key, []);

        if( empty($meta["og_title"]) && !empty($meta["title"]) ) {
            $meta["og_title"] = $meta["title"];
        }

        if( empty($meta["og_description"]) && !empty($meta["description"]) ) {
            $meta["og_description"] = $meta["description"];
        }

        return $meta;
    }

    public static function set(array $data): void
    {
        $key = request()->route()?->getName() ?? "";

        if( empty($key) ) return;

        self::$data[$key] = $data;
    }
}
