<?php

namespace App\Models\SGC;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $table = "sgc_companies";

    protected $fillable = [
        "tenant_id",
        "parent_id",
        "legal_name",
        "trade_name",
        "document",
        "street",
        "number",
        "district",
        "city",
        "state",
        "zip_code",
        "country",
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, "parent_id");
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, "parent_id");
    }
}
