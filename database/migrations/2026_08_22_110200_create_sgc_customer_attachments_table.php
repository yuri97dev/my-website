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
        Schema::table("sgc_customers", function (Blueprint $table) {
            $table->string("photo_path", 500)
                ->nullable()
                ->after("birth_date");
        });

        Schema::create("sgc_customer_attachments", function (Blueprint $table) {
            $table->id();

            $table->foreignId("company_customer_id")
                ->constrained("sgc_company_customer")
                ->restrictOnDelete();

            $table->string("type");
            $table->string("original_name");
            $table->string("path", 500);
            $table->string("mime_type", 127)->nullable();
            $table->unsignedBigInteger("size_bytes")->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("sgc_customer_attachments");

        Schema::table("sgc_customers", function (Blueprint $table) {
            $table->dropColumn("photo_path");
        });
    }
};
