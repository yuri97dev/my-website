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
        Schema::create("spreadsheet_jobs", function (Blueprint $table) {
            $table->id();
            $table->uuid("uuid")->unique();
            $table->foreignId("user_id")->nullable()->constrained()->nullOnDelete();
            $table->string("session_id")->nullable()->index();
            $table->string("original_name");
            $table->string("extension", 10);
            $table->unsignedBigInteger("size_bytes");
            $table->string("saved_as");
            $table->string("status", 20)->default("queued");
            $table->unsignedTinyInteger("progress")->default(0);
            $table->json("format_options");
            $table->string("result_path")->nullable();
            $table->string("result_name")->nullable();
            $table->text("error_message")->nullable();
            $table->timestamp("expires_at")->index();
            $table->timestamps();

            $table->index(["user_id", "expires_at"]);
            $table->index(["session_id", "expires_at"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("spreadsheet_jobs");
    }
};
