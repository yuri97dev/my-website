<?php

namespace App\Models\SGC;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantProbe extends Model
{
    use BelongsToTenant, BelongsToCompany;

    protected $table = "sgc_tenant_probes";

    protected $fillable = [
        "tenant_id",
        "company_id",
        "label",
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
