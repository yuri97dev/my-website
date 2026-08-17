<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SgcCustomer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        "name",
        "nickname",
        "identity_document",
        "tax_id",
        "birth_date",
    ];

    protected function casts(): array
    {
        return [
            "birth_date" => "date",
        ];
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(SgcCustomerAddress::class, "customer_id");
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(SgcCompany::class, "sgc_company_customer", "customer_id", "company_id")
            ->withPivot("notes")
            ->withTimestamps();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(SgcOrder::class, "customer_id");
    }

    public function isAccessibleBy(User $user): bool
    {
        return $this->companies()
            ->accessibleBy($user)
            ->exists();
    }
}
