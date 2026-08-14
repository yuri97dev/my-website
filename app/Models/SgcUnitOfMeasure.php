<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SgcUnitOfMeasure extends Model
{
    protected $table = "sgc_units_of_measure";

    protected $fillable = [
        "abbreviation",
        "name",
    ];
}
