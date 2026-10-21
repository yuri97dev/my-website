<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public $incrementing = false;

    protected $primaryKey = "key";

    protected $keyType = "string";

    protected $fillable = [
        "key",
        "value",
    ];

    public static function getValue(string $key, ?string $default = null): ?string
    {
        $value = static::query()->where("key", $key)->value("value");

        if( $value === null ) {
            return $default;
        }

        return $value;
    }

    public static function setValue(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(
            ["key" => $key],
            ["value" => $value],
        );
    }
}
