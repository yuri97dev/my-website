<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SgcOrderItem extends Model
{
    protected $fillable = [
        "order_id",
        "product_id",
        "unit_of_measure_id",
        "quantity",
        "unit_price",
        "discount_amount",
    ];

    protected function casts(): array
    {
        return [
            "quantity" => "decimal:3",
            "unit_price" => "decimal:2",
            "discount_amount" => "decimal:2",
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(SgcOrder::class, "order_id");
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(SgcProduct::class, "product_id");
    }

    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(SgcUnitOfMeasure::class, "unit_of_measure_id");
    }
}
