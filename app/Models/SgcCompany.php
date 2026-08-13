<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SgcCompany extends Model
{
    use SoftDeletes;

    public const ROLE_ADMIN = "admin";
    public const ROLE_SELLER = "seller";

    protected $fillable = [
        "owner_id",
        "parent_id",
        "legal_name",
        "trade_name",
        "document",
        "address_street",
        "address_number",
        "address_complement",
        "address_district",
        "address_city",
        "address_state",
        "address_postal_code",
        "address_country",
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, "owner_id");
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(SgcCompany::class, "parent_id");
    }

    public function children(): HasMany
    {
        return $this->hasMany(SgcCompany::class, "parent_id");
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, "sgc_company_user", "company_id", "user_id")
            ->withPivot("role")
            ->withTimestamps();
    }

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(SgcCustomer::class, "sgc_company_customer", "company_id", "customer_id")
            ->withPivot("notes")
            ->withTimestamps();
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(SgcProduct::class, "sgc_company_product", "company_id", "product_id")
            ->withPivot([
                "notes",
                "sale_price",
                "cost_price",
                "currency",
            ])
            ->withTimestamps();
    }

    public function scopeAccessibleBy(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $query) use ($user) {
            $query->where("owner_id", $user->id)
                ->orWhereHas("users", function (Builder $query) use ($user) {
                    $query->where("users.id", $user->id);
                });
        });
    }

    public function isAccessibleBy(User $user): bool
    {
        if( $this->owner_id === $user->id ) {
            return true;
        }

        return $this->users()->where("users.id", $user->id)->exists();
    }
}
