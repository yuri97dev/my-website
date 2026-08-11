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
        Schema::create("postal_code_addresses", function (Blueprint $table) {
            $table->id();

            $table->string("postal_code", 8)->unique();
            $table->string("address_street")->nullable();
            $table->string("address_district")->nullable();
            $table->string("address_city")->nullable();
            $table->string("address_state", 2)->nullable();
            $table->string("address_country", 2)->default("BR");

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("postal_code_addresses");
    }
};
