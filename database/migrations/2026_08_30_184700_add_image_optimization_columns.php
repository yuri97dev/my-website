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
        Schema::table("post_images", function (Blueprint $table) {
            $table->unsignedBigInteger("size_bytes")->nullable()->after("path");
            $table->unsignedBigInteger("original_size_bytes")->nullable()->after("size_bytes");
            $table->unsignedTinyInteger("optimization_attempts")->default(0)->after("original_size_bytes");
            $table->timestamp("optimized_at")->nullable()->after("optimization_attempts");

            $table->index(["optimized_at", "optimization_attempts"], "post_images_optimization_idx");
        });

        Schema::table("scraped_article_images", function (Blueprint $table) {
            $table->unsignedBigInteger("size_bytes")->nullable()->after("path");
            $table->unsignedBigInteger("original_size_bytes")->nullable()->after("size_bytes");
            $table->unsignedTinyInteger("optimization_attempts")->default(0)->after("original_size_bytes");
            $table->timestamp("optimized_at")->nullable()->after("optimization_attempts");

            $table->index(["optimized_at", "optimization_attempts"], "scraped_article_images_optimization_idx");
        });

        Schema::table("sgc_customer_attachments", function (Blueprint $table) {
            $table->unsignedBigInteger("original_size_bytes")->nullable()->after("size_bytes");
            $table->unsignedTinyInteger("optimization_attempts")->default(0)->after("original_size_bytes");
            $table->timestamp("optimized_at")->nullable()->after("optimization_attempts");

            $table->index(["optimized_at", "optimization_attempts"], "sgc_customer_attachments_optimization_idx");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("post_images", function (Blueprint $table) {
            $table->dropIndex("post_images_optimization_idx");

            $table->dropColumn([
                "size_bytes",
                "original_size_bytes",
                "optimization_attempts",
                "optimized_at",
            ]);
        });

        Schema::table("scraped_article_images", function (Blueprint $table) {
            $table->dropIndex("scraped_article_images_optimization_idx");

            $table->dropColumn([
                "size_bytes",
                "original_size_bytes",
                "optimization_attempts",
                "optimized_at",
            ]);
        });

        Schema::table("sgc_customer_attachments", function (Blueprint $table) {
            $table->dropIndex("sgc_customer_attachments_optimization_idx");

            $table->dropColumn([
                "original_size_bytes",
                "optimization_attempts",
                "optimized_at",
            ]);
        });
    }
};
