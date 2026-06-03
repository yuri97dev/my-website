<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;

class UploadLimit
{
    public static function spreadsheetFormatter(): int
    {
        $user = Auth::user();
        return $user ? 10240 : 4096;
    }
}