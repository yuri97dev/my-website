<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table("sgc_customer_addresses", function (Blueprint $table) {
            $table->string("address_reference_point")
                ->nullable()
                ->after("address_complement");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("sgc_customer_addresses", function (Blueprint $table) {
            $table->dropColumn("address_reference_point");
        });
    }
};
