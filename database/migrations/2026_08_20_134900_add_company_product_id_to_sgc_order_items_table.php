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
        Schema::table("sgc_order_items", function (Blueprint $table) {
            $table->foreignId("company_product_id")
                ->nullable()
                ->after("product_id")
                ->constrained("sgc_company_product")
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("sgc_order_items", function (Blueprint $table) {
            $table->dropConstrainedForeignId("company_product_id");
        });
    }
};
