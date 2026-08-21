<?php

namespace Database\Seeders;

use App\Models\SgcCustomer;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SgcFinalConsumerSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the default final consumer customer record.
     */
    public function run(): void
    {
        $exists = SgcCustomer::withTrashed()
            ->whereKey(SgcCustomer::FINAL_CONSUMER_ID)
            ->exists();

        if( $exists ) {
            SgcCustomer::withTrashed()
                ->whereKey(SgcCustomer::FINAL_CONSUMER_ID)
                ->update([
                    "name" => SgcCustomer::FINAL_CONSUMER_NAME,
                    "deleted_at" => null,
                ]);

            return;
        }

        $now = now();

        DB::table("sgc_customers")->insert([
            "id" => SgcCustomer::FINAL_CONSUMER_ID,
            "name" => SgcCustomer::FINAL_CONSUMER_NAME,
            "created_at" => $now,
            "updated_at" => $now,
        ]);

        DB::statement(
            "SELECT setval(
                pg_get_serial_sequence('sgc_customers', 'id'),
                GREATEST((SELECT COALESCE(MAX(id), 1) FROM sgc_customers), 1)
            )"
        );
    }
}
