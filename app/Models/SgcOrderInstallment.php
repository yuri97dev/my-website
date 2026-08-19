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
        "paid_amount",
        "paid_at",
    ];

    protected function casts(): array
    {
        return [
            "due_date" => "date",
            "amount" => "decimal:2",
            "paid_amount" => "decimal:2",
            "paid_at" => "datetime",
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(SgcOrder::class, "order_id");
    }

    public function remainingAmount(): float
    {
        return max(0, round((float) $this->amount - (float) $this->paid_amount, 2));
    }

    public function isPaid(): bool
    {
        return $this->remainingAmount() <= 0;
    }

    public function applyPayment(float $amount): void
    {
        $total = round((float) $this->amount, 2);
        $paid_amount = min(round((float) $this->paid_amount + $amount, 2), $total);

        $this->paid_amount = $paid_amount;

        $this->paid_at = $paid_amount >= $total
            ? ($this->paid_at ?? now())
            : null;

        $this->save();
    }
}
