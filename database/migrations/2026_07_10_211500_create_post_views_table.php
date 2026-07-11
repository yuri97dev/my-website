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
        Schema::table("posts", function (Blueprint $table) {
            $table->unsignedBigInteger("views_count")->default(0)->after("thumbnail_path");
        });

        Schema::create("post_views", function (Blueprint $table) {
            $table->id();

            $table->foreignId("post_id")
                ->constrained()
                ->cascadeOnDelete();

            $table->string("visitor_hash", 64);
            $table->foreignId("user_id")
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string("referrer", 500)->nullable();
            $table->timestamp("viewed_at");

            $table->timestamps();

            $table->index(["post_id", "viewed_at"]);
            $table->index(["post_id", "visitor_hash"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("post_views");

        Schema::table("posts", function (Blueprint $table) {
            $table->dropColumn("views_count");
        });
    }
};
