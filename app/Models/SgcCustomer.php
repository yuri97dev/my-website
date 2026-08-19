<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

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

    public static function normalizeIdentityDocument(mixed $value): ?string
    {
        $normalized = self::unmaskDocument($value);

        if( $normalized === null ) {
            return null;
        }

        return mb_strtoupper($normalized);
    }

    public static function normalizeTaxId(mixed $value): ?string
    {
        $normalized = self::unmaskDocument($value);

        if( $normalized === null ) {
            return null;
        }

        $normalized = preg_replace("/[^A-Za-z0-9]/", "", $normalized);

        if( $normalized === "" ) {
            return null;
        }

        return mb_strtoupper($normalized);
    }

    public static function unmaskDocument(mixed $value): ?string
    {
        if( $value === null ) {
            return null;
        }

        $normalized = preg_replace("/[.\/\s-]/", "", trim((string) $value));

        return $normalized === "" ? null : $normalized;
    }

    /**
     * @return Collection<int, self>
     */
    public static function findByDocuments(?string $identity_document, ?string $tax_id): Collection
    {
        $identity_document = self::normalizeIdentityDocument($identity_document);
        $tax_id = self::normalizeTaxId($tax_id);

        if( $identity_document === null && $tax_id === null ) {
            return collect();
        }

        return static::query()
            ->where(function ($query) use ($identity_document, $tax_id) {
                if( $identity_document !== null ) {
                    $query->orWhereRaw(
                        "upper(".self::unmaskSql("sgc_customers.identity_document").") = ?",
                        [ $identity_document ]
                    );
                }

                if( $tax_id !== null ) {
                    $query->orWhereRaw(
                        "upper(".self::unmaskSql("sgc_customers.tax_id").") = ?",
                        [ $tax_id ]
                    );
                }
            })
            ->get();
    }

    private static function unmaskSql(string $column): string
    {
        return "replace(replace(replace(replace({$column}, '.', ''), '-', ''), '/', ''), ' ', '')";
    }
}
