<?php

namespace App\Helpers;

use Illuminate\Support\Arr;

class Meta
{
    public static function get(string $key): array
    {
        if( empty($key) ) return [];

        $meta = Arr::get(config("meta"), $key, []);

        if( empty($meta["og_title"]) && !empty($meta["title"]) ) {
            $meta["og_title"] = $meta["title"];
        }

        if( empty($meta["og_description"]) && !empty($meta["description"]) ) {
            $meta["og_description"] = $meta["description"];
        }

        return $meta;
    }
}
