<?php

namespace Database\Seeders;

use App\Models\SgcPaymentMethod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SgcPaymentMethodSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's payment method catalog.
     */
    public function run(): void
    {
        $payment_methods = [
            [
                "slug" => SgcPaymentMethod::CASH,
                "name" => "Dinheiro",
            ],
            [
                "slug" => SgcPaymentMethod::PIX,
                "name" => "Pix",
            ],
            [
                "slug" => SgcPaymentMethod::DEBIT_CARD,
                "name" => "Cartão de débito",
            ],
            [
                "slug" => SgcPaymentMethod::CREDIT_CARD,
                "name" => "Cartão de crédito",
            ],
        ];

        foreach ($payment_methods as $payment_method) {
            SgcPaymentMethod::query()->firstOrCreate(
                ["slug" => $payment_method["slug"]],
                [
                    "name" => $payment_method["name"],
                    "surcharge_percentage" => 0,
                ],
            );
        }
    }
}
