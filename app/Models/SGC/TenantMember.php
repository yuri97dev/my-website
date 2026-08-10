<?php

namespace App\Models\SGC;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantMember extends Model
{
    public const ROLE_ADMIN = "admin";

    public const ROLE_SELLER = "seller";

    protected $table = "sgc_tenant_members";

    protected $fillable = [
        "tenant_id",
        "user_id",
        "role",
        "company_id",
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
