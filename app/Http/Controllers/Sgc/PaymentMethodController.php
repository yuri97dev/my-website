<?php

namespace App\Http\Controllers\Sgc;

use App\Http\Controllers\Controller;
use App\Models\SgcPaymentMethod;

class PaymentMethodController extends Controller
{
    public function index()
    {
        $payment_methods = SgcPaymentMethod::query()
            ->orderBy("name")
            ->get();

        return response()->json([
            "payment_methods" => $payment_methods,
        ], 200);
    }
}
