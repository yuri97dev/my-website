<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SgcCompanyProductStockMovement extends Model
{
    public const TYPE_ENTRY = "entry";

    public const TYPE_SALE = "sale";

    public const TYPE_CANCELLATION = "cancellation";

    public const TYPE_DELETION = "deletion";

    public const TYPE_ADJUSTMENT = "adjustment";

    public const TYPES = [
        self::TYPE_ENTRY,
        self::TYPE_SALE,
        self::TYPE_CANCELLATION,
        self::TYPE_DELETION,
        self::TYPE_ADJUSTMENT,
    ];

    public const TYPES_REQUIRING_REASON = [
        self::TYPE_CANCELLATION,
        self::TYPE_DELETION,
        self::TYPE_ADJUSTMENT,
    ];

    protected $fillable = [
        "company_product_id",
        "company_id",
        "product_id",
        "user_id",
        "reference_type",
        "reference_id",
        "type",
        "quantity_before",
        "quantity_after",
        "quantity_delta",
        "reason",
    ];

    protected function casts(): array
    {
        return [
            "quantity_before" => "decimal:3",
            "quantity_after" => "decimal:3",
            "quantity_delta" => "decimal:3",
        ];
    }

    public function companyProduct(): BelongsTo
    {
        return $this->belongsTo(SgcCompanyProduct::class, "company_product_id");
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(SgcCompany::class, "company_id");
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(SgcProduct::class, "product_id");
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, "user_id");
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
