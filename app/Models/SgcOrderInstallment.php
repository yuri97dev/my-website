<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SgcOrderInstallment extends Model
{
    protected $fillable = [
        "order_id",
        "installment_number",
        "due_date",
        "amount",
        "paid_at",
    ];

    protected function casts(): array
    {
        return [
            "due_date" => "date",
            "amount" => "decimal:2",
            "paid_at" => "datetime",
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(SgcOrder::class, "order_id");
    }
}
