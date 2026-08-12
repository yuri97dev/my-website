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
        Schema::table("users", function (Blueprint $table) {
            $table->string("identity_document")->nullable()->after("accepts_marketing");
            $table->string("tax_id")->nullable()->unique()->after("identity_document");
            $table->string("phone_number")->nullable()->after("tax_id");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("users", function (Blueprint $table) {
            $table->dropColumn(["identity_document", "tax_id", "phone_number"]);
        });
    }
};
