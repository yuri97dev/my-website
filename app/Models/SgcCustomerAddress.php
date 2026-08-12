<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SgcCustomerAddress extends Model
{
    protected $fillable = [
        "customer_id",
        "address_street",
        "address_number",
        "address_complement",
        "address_district",
        "address_city",
        "address_state",
        "address_postal_code",
        "address_country",
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(SgcCustomer::class, "customer_id");
    }
}
