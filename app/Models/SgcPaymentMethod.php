<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SgcPaymentMethod extends Model
{
    public const CASH = "cash";

    public const PIX = "pix";

    public const DEBIT_CARD = "debit_card";

    public const CREDIT_CARD = "credit_card";

    public const STORE_CREDIT = "store_credit";

    public const INSTALLMENT_SLUGS = [
        self::CREDIT_CARD,
        self::STORE_CREDIT,
    ];

    protected $fillable = [
        "slug",
        "name",
        "surcharge_percentage",
    ];

    protected function casts(): array
    {
        return [
            "surcharge_percentage" => "decimal:2",
        ];
    }

    protected $appends = [
        "supports_installments",
    ];

    public function getSupportsInstallmentsAttribute(): bool
    {
        return in_array($this->slug, self::INSTALLMENT_SLUGS, true);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(SgcOrder::class, "payment_method_id");
    }
}
