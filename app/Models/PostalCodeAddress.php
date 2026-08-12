<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostalCodeAddress extends Model
{
    protected $fillable = [
        "postal_code",
        "address_street",
        "address_district",
        "address_city",
        "address_state",
        "address_country",
    ];
}
