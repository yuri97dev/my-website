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
        Schema::create("sgc_payment_methods", function (Blueprint $table) {
            $table->id();

            $table->string("slug")->unique();
            $table->string("name")->unique();
            $table->decimal("surcharge_percentage", 5, 2)->default(0);

            $table->timestamps();
        });

        Schema::create("sgc_orders", function (Blueprint $table) {
            $table->id();

            $table->foreignId("company_id")
                ->nullable()
                ->constrained("sgc_companies")
                ->nullOnDelete();

            $table->foreignId("customer_id")
                ->nullable()
                ->constrained("sgc_customers")
                ->nullOnDelete();

            $table->foreignId("created_by_id")
                ->nullable()
                ->constrained("users")
                ->nullOnDelete();

            $table->foreignId("customer_address_id")
                ->nullable()
                ->constrained("sgc_customer_addresses")
                ->nullOnDelete();

            $table->foreignId("payment_method_id")
                ->nullable()
                ->constrained("sgc_payment_methods")
                ->nullOnDelete();

            $table->uuid("uuid")->unique();
            $table->string("status")->default("draft");

            $table->decimal("discount_amount", 12, 2)->default(0);
            $table->decimal("total", 12, 2)->default(0);
            $table->char("currency", 3)->default("BRL");

            $table->string("notes", 500)->nullable();
            $table->timestamp("ordered_at")->nullable();

            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create("sgc_order_items", function (Blueprint $table) {
            $table->id();

            $table->foreignId("order_id")
                ->constrained("sgc_orders")
                ->cascadeOnDelete();

            $table->foreignId("product_id")
                ->nullable()
                ->constrained("sgc_products")
                ->nullOnDelete();

            $table->foreignId("unit_of_measure_id")
                ->nullable()
                ->constrained("sgc_units_of_measure")
                ->nullOnDelete();

            $table->decimal("quantity", 12, 3);
            $table->decimal("unit_price", 12, 2);
            $table->decimal("discount_amount", 12, 2)->default(0);

            $table->timestamps();
        });

        Schema::create("sgc_order_installments", function (Blueprint $table) {
            $table->id();

            $table->foreignId("order_id")
                ->constrained("sgc_orders")
                ->cascadeOnDelete();

            $table->unsignedSmallInteger("installment_number");
            $table->date("due_date")->nullable();
            $table->decimal("amount", 12, 2);
            $table->timestamp("paid_at")->nullable();

            $table->timestamps();

            $table->unique(["order_id", "installment_number"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("sgc_order_installments");
        Schema::dropIfExists("sgc_payment_methods");
        Schema::dropIfExists("sgc_order_items");
        Schema::dropIfExists("sgc_orders");
    }
};
