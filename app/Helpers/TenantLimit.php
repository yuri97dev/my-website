<?php

namespace App\Helpers;

use App\Models\User;

class TenantLimit
{
    public static function companies(?User $user = null): int
    {
        return 3;
    }

    public static function members(?User $user = null): int
    {
        return 5;
    }

    public static function records(?User $user = null): int
    {
        return 10000;
    }
}
