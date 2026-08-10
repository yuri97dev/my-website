<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class SGCContext
{
    private static ?int $tenant_id = null;

    private static ?int $company_id = null;

    public static function set(int $tenant_id, int $company_id): void
    {
        self::$tenant_id = $tenant_id;
        self::$company_id = $company_id;
    }

    public static function tenantId(): ?int
    {
        return self::$tenant_id;
    }

    public static function companyId(): ?int
    {
        return self::$company_id;
    }

    public static function forget(): void
    {
        self::$tenant_id = null;
        self::$company_id = null;
    }

    public static function applyRls(): void
    {
        $tenant_id = self::tenantId();

        if( $tenant_id !== null ) {
            DB::statement("SET LOCAL app.tenant_id = ?", [(string) $tenant_id]);
        }
    }
}
