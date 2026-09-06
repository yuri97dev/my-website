<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table("scraped_articles", function (Blueprint $table) {
            $table->text("assistant_selection_prompt")->nullable()->after("processing_at");
        });
    }

    public function down(): void
    {
        Schema::table("scraped_articles", function (Blueprint $table) {
            $table->dropColumn("assistant_selection_prompt");
        });
    }
};
