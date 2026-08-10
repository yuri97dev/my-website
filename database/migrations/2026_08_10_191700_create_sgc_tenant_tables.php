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
        Schema::create("sgc_tenants", function (Blueprint $table) {
            $table->id();
            $table->foreignId("owner_id")->constrained("users")->cascadeOnDelete();
            $table->enum("status", ["active", "suspended"])->default("active");
            $table->string("plan", 20)->default("free");
            $table->timestamps();

            $table->unique("owner_id");
        });

        Schema::create("sgc_companies", function (Blueprint $table) {
            $table->id();
            $table->foreignId("tenant_id")->constrained("sgc_tenants")->cascadeOnDelete();
            $table->foreignId("parent_id")->nullable()->constrained("sgc_companies")->nullOnDelete();
            $table->string("legal_name");
            $table->string("trade_name");
            $table->string("document", 20)->nullable();
            $table->string("street");
            $table->string("number", 20);
            $table->string("district", 100);
            $table->string("city", 100);
            $table->string("state", 2);
            $table->string("zip_code", 8);
            $table->string("country", 2)->default("BR");
            $table->timestamps();

            $table->index("tenant_id");
        });

        Schema::create("sgc_tenant_members", function (Blueprint $table) {
            $table->id();
            $table->foreignId("tenant_id")->constrained("sgc_tenants")->cascadeOnDelete();
            $table->foreignId("user_id")->constrained("users")->cascadeOnDelete();
            $table->enum("role", ["admin", "seller"]);
            $table->foreignId("company_id")->nullable()->constrained("sgc_companies")->nullOnDelete();
            $table->timestamps();

            $table->unique(["tenant_id", "user_id"]);
            $table->index("tenant_id");
            $table->index("user_id");
        });

        Schema::create("sgc_tenant_probes", function (Blueprint $table) {
            $table->id();
            $table->foreignId("tenant_id")->constrained("sgc_tenants")->cascadeOnDelete();
            $table->foreignId("company_id")->constrained("sgc_companies")->cascadeOnDelete();
            $table->string("label");
            $table->timestamps();

            $table->index(["tenant_id", "company_id"]);
        });

        if( Schema::getConnection()->getDriverName() === "pgsql" ) {
            DB::statement("ALTER TABLE sgc_tenant_probes ENABLE ROW LEVEL SECURITY");

            DB::statement("
                CREATE POLICY tenant_isolation ON sgc_tenant_probes
                    USING (tenant_id = current_setting('app.tenant_id', true)::bigint)
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if( Schema::getConnection()->getDriverName() === "pgsql" ) {
            DB::statement("DROP POLICY IF EXISTS tenant_isolation ON sgc_tenant_probes");
        }

        Schema::dropIfExists("sgc_tenant_probes");
        Schema::dropIfExists("sgc_tenant_members");
        Schema::dropIfExists("sgc_companies");
        Schema::dropIfExists("sgc_tenants");
    }
};
