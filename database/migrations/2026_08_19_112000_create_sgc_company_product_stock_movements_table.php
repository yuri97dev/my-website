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
        Schema::create("sgc_company_product_stock_movements", function (Blueprint $table) {
            $table->id();

            $table->foreignId("company_product_id")
                ->nullable()
                ->constrained("sgc_company_product")
                ->nullOnDelete();

            $table->foreignId("company_id")
                ->constrained("sgc_companies")
                ->cascadeOnDelete();

            $table->foreignId("product_id")
                ->nullable()
                ->constrained("sgc_products")
                ->nullOnDelete();

            $table->foreignId("user_id")
                ->nullable()
                ->constrained("users")
                ->nullOnDelete();

            $table->nullableMorphs("reference", "sgc_stock_movements_reference_idx");

            $table->string("type");
            $table->decimal("quantity_before", 12, 3);
            $table->decimal("quantity_after", 12, 3);
            $table->decimal("quantity_delta", 12, 3);
            $table->text("reason")->nullable();

            $table->timestamps();

            $table->index(["company_product_id", "created_at"], "sgc_stock_movements_product_created_idx");
            $table->index(["company_id", "product_id"], "sgc_stock_movements_company_product_idx");
            $table->index("type", "sgc_stock_movements_type_idx");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("sgc_company_product_stock_movements");
    }
};
