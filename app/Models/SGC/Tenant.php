<?php

namespace App\Models\SGC;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    public const STATUS_ACTIVE = "active";

    public const STATUS_SUSPENDED = "suspended";

    protected $table = "sgc_tenants";

    protected $fillable = [
        "owner_id",
        "status",
        "plan",
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, "owner_id");
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(TenantMember::class);
    }
}
