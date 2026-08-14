<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("CREATE EXTENSION IF NOT EXISTS pg_trgm");

        Schema::create("sgc_companies", function (Blueprint $table) {
            $table->id();

            $table->foreignId("owner_id")
                ->constrained("users")
                ->cascadeOnDelete();

            $table->foreignId("parent_id")
                ->nullable()
                ->constrained("sgc_companies")
                ->nullOnDelete();

            $table->string("legal_name");
            $table->string("trade_name")->nullable();
            $table->string("document")->nullable();
            $table->string("address_street")->nullable();
            $table->string("address_number")->nullable();
            $table->string("address_complement")->nullable();
            $table->string("address_district")->nullable();
            $table->string("address_city")->nullable();
            $table->string("address_state")->nullable();
            $table->string("address_postal_code")->nullable();
            $table->string("address_country")->nullable();

            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create("sgc_company_user", function (Blueprint $table) {
            $table->id();

            $table->foreignId("company_id")
                ->constrained("sgc_companies")
                ->cascadeOnDelete();

            $table->foreignId("user_id")
                ->constrained()
                ->cascadeOnDelete();

            $table->string("role");

            $table->timestamps();

            $table->unique(["company_id", "user_id"]);
        });

        Schema::create("sgc_customers", function (Blueprint $table) {
            $table->id();

            $table->string("name");
            $table->string("nickname")->nullable();
            $table->string("identity_document")->nullable();
            $table->string("tax_id")->nullable();
            $table->date("birth_date")->nullable();

            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create("sgc_customer_addresses", function (Blueprint $table) {
            $table->id();

            $table->foreignId("customer_id")
                ->constrained("sgc_customers")
                ->cascadeOnDelete();

            $table->string("address_street")->nullable();
            $table->string("address_number")->nullable();
            $table->string("address_complement")->nullable();
            $table->string("address_district")->nullable();
            $table->string("address_city")->nullable();
            $table->string("address_state")->nullable();
            $table->string("address_postal_code")->nullable();
            $table->string("address_country")->nullable();

            $table->timestamps();
        });

        Schema::create("sgc_company_customer", function (Blueprint $table) {
            $table->id();

            $table->foreignId("company_id")
                ->constrained("sgc_companies")
                ->cascadeOnDelete();

            $table->foreignId("customer_id")
                ->constrained("sgc_customers")
                ->cascadeOnDelete();

            $table->text("notes")->nullable();

            $table->timestamps();

            $table->unique(["company_id", "customer_id"]);
        });

        Schema::create("sgc_brands", function (Blueprint $table) {
            $table->id();

            $table->string("name")->unique();
            $table->text("description")->nullable();

            $table->timestamps();
        });

        Schema::create("sgc_product_categories", function (Blueprint $table) {
            $table->id();

            $table->string("name")->unique();
            $table->text("description")->nullable();

            $table->timestamps();
        });

        Schema::create("sgc_products", function (Blueprint $table) {
            $table->id();

            $table->foreignId("brand_id")
                ->nullable()
                ->constrained("sgc_brands")
                ->nullOnDelete();

            $table->foreignId("product_category_id")
                ->nullable()
                ->constrained("sgc_product_categories")
                ->nullOnDelete();

            $table->string("name");
            $table->text("technical_specifications")->nullable();

            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create("sgc_units_of_measure", function (Blueprint $table) {
            $table->id();

            $table->string("abbreviation", 8)->unique();
            $table->string("name")->unique();

            $table->timestamps();
        });

        Schema::create("sgc_company_product", function (Blueprint $table) {
            $table->id();

            $table->foreignId("company_id")
                ->constrained("sgc_companies")
                ->cascadeOnDelete();

            $table->foreignId("product_id")
                ->constrained("sgc_products")
                ->cascadeOnDelete();

            $table->foreignId("unit_of_measure_id")
                ->nullable()
                ->constrained("sgc_units_of_measure")
                ->nullOnDelete();

            $table->decimal("sale_price", 12, 2)->nullable();
            $table->decimal("cost_price", 12, 2)->nullable();
            $table->decimal("stock_quantity", 12, 3)->default(0);
            $table->string("serial_number")->nullable();
            $table->char("currency", 3)->default("BRL");

            $table->text("notes")->nullable();

            $table->timestamps();

            $table->unique(["company_id", "product_id"]);
            $table->unique(["product_id", "serial_number"]);
        });

        DB::statement(
            "CREATE INDEX sgc_customers_name_trgm_idx ON sgc_customers USING gin (name gin_trgm_ops)"
        );
        DB::statement(
            "CREATE INDEX sgc_customers_nickname_trgm_idx ON sgc_customers USING gin (nickname gin_trgm_ops)"
        );
        DB::statement(
            "CREATE INDEX sgc_products_name_trgm_idx ON sgc_products USING gin (name gin_trgm_ops)"
        );

        DB::statement(
            "CREATE INDEX sgc_company_product_product_id_idx ON sgc_company_product (product_id)"
        );
        DB::statement(
            "CREATE INDEX sgc_company_customer_customer_id_idx ON sgc_company_customer (customer_id)"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP INDEX IF EXISTS sgc_company_customer_customer_id_idx");
        DB::statement("DROP INDEX IF EXISTS sgc_company_product_product_id_idx");
        DB::statement("DROP INDEX IF EXISTS sgc_products_name_trgm_idx");
        DB::statement("DROP INDEX IF EXISTS sgc_customers_nickname_trgm_idx");
        DB::statement("DROP INDEX IF EXISTS sgc_customers_name_trgm_idx");

        Schema::dropIfExists("sgc_company_product");
        Schema::dropIfExists("sgc_units_of_measure");
        Schema::dropIfExists("sgc_products");
        Schema::dropIfExists("sgc_product_categories");
        Schema::dropIfExists("sgc_brands");
        Schema::dropIfExists("sgc_company_customer");
        Schema::dropIfExists("sgc_customer_addresses");
        Schema::dropIfExists("sgc_customers");
        Schema::dropIfExists("sgc_company_user");
        Schema::dropIfExists("sgc_companies");
    }
};
