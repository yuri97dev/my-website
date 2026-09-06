<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table("scraped_articles", function (Blueprint $table) {
            $table->foreignId("processing_by")
                ->nullable()
                ->after("translation_attempts")
                ->constrained("users")
                ->nullOnDelete();

            $table->timestamp("processing_at")->nullable()->after("processing_by");

            $table->index(["processing_by", "processing_at"]);
        });
    }

    public function down(): void
    {
        Schema::table("scraped_articles", function (Blueprint $table) {
            $table->dropIndex(["processing_by", "processing_at"]);
            $table->dropColumn(["processing_by", "processing_at"]);
        });
    }
};
