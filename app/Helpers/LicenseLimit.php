<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;

class LicenseLimit
{
    public static function perUser(): int
    {
        return Auth::user() ? 5 : 0;
    }
}
