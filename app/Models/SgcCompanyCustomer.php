<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SgcCompanyCustomer extends Model
{
    protected $table = "sgc_company_customer";

    protected $fillable = [
        "company_id",
        "customer_id",
        "notes",
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(SgcCompany::class, "company_id");
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(SgcCustomer::class, "customer_id");
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SgcCustomerAttachment::class, "company_customer_id");
    }

    public function isAccessibleBy(User $user): bool
    {
        return $this->company?->isAccessibleBy($user) ?? false;
    }
}
