<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class EncryptedString implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return empty($value) ? null : Crypt::decryptString($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return empty($value) ? null : Crypt::encryptString($value);
    }
}
