<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SgcOrder extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = "draft";

    public const STATUS_CONFIRMED = "confirmed";

    public const STATUS_CANCELLED = "cancelled";

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_CONFIRMED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        "company_id",
        "customer_id",
        "created_by_id",
        "customer_address_id",
        "payment_method_id",
        "uuid",
        "status",
        "discount_amount",
        "total",
        "currency",
        "notes",
        "ordered_at",
    ];

    protected function casts(): array
    {
        return [
            "discount_amount" => "decimal:2",
            "total" => "decimal:2",
            "ordered_at" => "datetime",
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(SgcCompany::class, "company_id");
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(SgcCustomer::class, "customer_id");
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, "created_by_id");
    }

    public function customerAddress(): BelongsTo
    {
        return $this->belongsTo(SgcCustomerAddress::class, "customer_address_id");
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(SgcPaymentMethod::class, "payment_method_id");
    }

    public function items(): HasMany
    {
        return $this->hasMany(SgcOrderItem::class, "order_id");
    }

    public function installments(): HasMany
    {
        return $this->hasMany(SgcOrderInstallment::class, "order_id");
    }

    public function isAccessibleBy(User $user): bool
    {
        return $this->company()
            ->accessibleBy($user)
            ->exists();
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function canBeEdited(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function clearInstallmentPayments(): void
    {
        $this->installments()->update([
            "paid_amount" => 0,
            "paid_at" => null,
        ]);
    }
}
