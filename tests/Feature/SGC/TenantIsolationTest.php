<?php

use App\Helpers\SGCContext;
use App\Helpers\TenantLimit;
use App\Models\SGC\Company;
use App\Models\SGC\Tenant;
use App\Models\SGC\TenantMember;
use App\Models\SGC\TenantProbe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * @group SGC-isolation
 */
function createTenantWithCompany(User $owner): array
{
    $tenant = Tenant::query()->create([
        "owner_id" => $owner->id,
        "status" => Tenant::STATUS_ACTIVE,
        "plan" => "free",
    ]);

    $company = Company::query()->create([
        "tenant_id" => $tenant->id,
        "legal_name" => "Empresa Teste LTDA",
        "trade_name" => "Empresa Teste",
        "street" => "Rua A",
        "number" => "100",
        "district" => "Centro",
        "city" => "São Paulo",
        "state" => "SP",
        "zip_code" => "01001000",
        "country" => "BR",
    ]);

    TenantMember::query()->create([
        "tenant_id" => $tenant->id,
        "user_id" => $owner->id,
        "role" => TenantMember::ROLE_ADMIN,
    ]);

    return [$tenant, $company];
}

afterEach(function () {
    SGCContext::forget();
});

test("SGCContext set get e forget funcionam", function () {
    SGCContext::set(1, 2);

    expect(SGCContext::tenantId())->toBe(1)
        ->and(SGCContext::companyId())->toBe(2);

    SGCContext::forget();

    expect(SGCContext::tenantId())->toBeNull()
        ->and(SGCContext::companyId())->toBeNull();
});

test("TenantLimit retorna limites estaticos", function () {
    expect(TenantLimit::companies())->toBe(3)
        ->and(TenantLimit::members())->toBe(5)
        ->and(TenantLimit::records())->toBe(10000);
});

test("tenant A nao le dados do tenant B com contexto A", function () {
    [$tenantA, $companyA] = createTenantWithCompany(User::factory()->create());
    [$tenantB, $companyB] = createTenantWithCompany(User::factory()->create());

    TenantProbe::withoutGlobalScopes()->create([
        "tenant_id" => $tenantA->id,
        "company_id" => $companyA->id,
        "label" => "probe-a",
    ]);

    TenantProbe::withoutGlobalScopes()->create([
        "tenant_id" => $tenantB->id,
        "company_id" => $companyB->id,
        "label" => "probe-b",
    ]);

    SGCContext::set($tenantA->id, $companyA->id);

    $labels = TenantProbe::query()->pluck("label")->all();

    expect($labels)->toBe(["probe-a"]);
});

test("sem SGCContext definido global scope retorna vazio", function () {
    [$tenant, $company] = createTenantWithCompany(User::factory()->create());

    TenantProbe::withoutGlobalScopes()->create([
        "tenant_id" => $tenant->id,
        "company_id" => $company->id,
        "label" => "probe",
    ]);

    expect(TenantProbe::query()->count())->toBe(0);
});

test("com RLS ativo query direta com tenant errado retorna zero linhas", function () {
    if( DB::connection()->getDriverName() !== "pgsql" ) {
        $this->markTestSkipped("RLS requer Postgres.");
    }

    [$tenantA, $companyA] = createTenantWithCompany(User::factory()->create());
    [$tenantB, $companyB] = createTenantWithCompany(User::factory()->create());

    TenantProbe::withoutGlobalScopes()->create([
        "tenant_id" => $tenantA->id,
        "company_id" => $companyA->id,
        "label" => "probe-a",
    ]);

    TenantProbe::withoutGlobalScopes()->create([
        "tenant_id" => $tenantB->id,
        "company_id" => $companyB->id,
        "label" => "probe-b",
    ]);

    $count = DB::transaction(function () use ($tenantA) {
        SGCContext::set($tenantA->id, 1);
        SGCContext::applyRls();

        return DB::table("sgc_tenant_probes")->count();
    });

    expect($count)->toBe(1);
});

test("traits preenchem tenant_id e company_id no creating", function () {
    [$tenant, $company] = createTenantWithCompany(User::factory()->create());

    SGCContext::set($tenant->id, $company->id);

    $probe = TenantProbe::query()->create([
        "label" => "auto-fill",
    ]);

    expect($probe->tenant_id)->toBe($tenant->id)
        ->and($probe->company_id)->toBe($company->id);
});
