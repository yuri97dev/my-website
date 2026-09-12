<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("licenses", function (Blueprint $table) {
            $table->id();
            $table->uuid("uuid")->unique();
            $table->foreignId("user_id")->constrained()->cascadeOnDelete();
            $table->string("label");
            $table->text("observations")->nullable();
            $table->string("status", 20)->default("pending");
            $table->date("expires_at");
            $table->string("fingerprint")->nullable();
            $table->text("public_key");
            $table->text("private_key_encrypted");
            $table->timestamps();

            $table->index(["user_id", "status"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("licenses");
    }
};
