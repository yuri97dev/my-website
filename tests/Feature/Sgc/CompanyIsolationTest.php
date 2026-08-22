<?php

use App\Models\SgcBrand;
use App\Models\SgcCompany;
use App\Models\SgcCompanyCustomer;
use App\Models\SgcCompanyProduct;
use App\Models\SgcCompanyUser;
use App\Models\SgcCustomer;
use App\Models\SgcCustomerAttachment;
use App\Models\SgcOrder;
use App\Models\SgcOrderInstallment;
use App\Models\SgcPaymentMethod;
use App\Models\SgcProduct;
use App\Models\SgcProductCategory;
use App\Models\SgcUnitOfMeasure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

dataset("pares_empresas_distintas", [
    "A contra B" => ["A", "B"],
    "A contra C" => ["A", "C"],
    "B contra A" => ["B", "A"],
    "B contra C" => ["B", "C"],
    "C contra A" => ["C", "A"],
    "C contra B" => ["C", "B"],
]);

dataset("empresas", ["A", "B", "C"]);

beforeEach(function () {
    seedSgcCatalog();

    $this->tenants = [
        "A" => makeSgcTenant("A", "11111111111", "111111111"),
        "B" => makeSgcTenant("B", "22222222222", "222222222"),
        "C" => makeSgcTenant("C", "33333333333", "333333333"),
    ];
});

describe("isolamento entre empresas (LGPD)", function () {
    it("lista apenas as empresas do próprio usuário", function (string $actor) {
        $response = asTenant($actor)->getJson("/api/tools/sgc/companies")->assertOk();

        expect(jsonIds($response, "companies"))
            ->toBe([tenant($actor)["company"]->id]);
    })->with("empresas");

    it("consulta e altera registros da própria empresa", function (string $key) {
        $tenant = tenant($key);
        $actor = asTenant($key);

        $actor->getJson("/api/tools/sgc/companies/{$tenant["company"]->id}")->assertOk();
        $actor->getJson("/api/tools/sgc/customers/{$tenant["customer"]->id}")->assertOk();
        $actor->getJson("/api/tools/sgc/products/{$tenant["product"]->id}")->assertOk();
        $actor->getJson("/api/tools/sgc/orders/{$tenant["draft_order"]->id}")->assertOk();
        $actor->getJson("/api/tools/sgc/employees/{$tenant["employee"]->id}")->assertOk();
        $actor->getJson("/api/tools/sgc/customers/{$tenant["customer"]->id}/addresses")->assertOk();

        $lookup = $actor->getJson("/api/tools/sgc/customers/lookup?tax_id={$tenant["customer"]->tax_id}")
            ->assertOk();

        expect($lookup->json("customer.id"))->toBe($tenant["customer"]->id);

        $actor->putJson("/api/tools/sgc/companies/{$tenant["company"]->id}", [
            "legal_name" => "EMPRESA {$key} ATUALIZADA LTDA",
        ])->assertOk();

        $actor->putJson("/api/tools/sgc/customers/{$tenant["customer"]->id}", [
            "company_id" => $tenant["company"]->id,
            "name" => "CLIENTE {$key} ATUALIZADO",
        ])->assertOk();

        $actor->putJson("/api/tools/sgc/products/{$tenant["product"]->id}", [
            "company_id" => $tenant["company"]->id,
            "name" => "PRODUTO {$key} ATUALIZADO",
            "sale_price" => 15.5,
        ])->assertOk();
    })->with("empresas");

    it("nao consulta nem altera a empresa de outro usuario", function (string $actor, string $target) {
        $foreign = tenant($target);
        $original_name = $foreign["company"]->legal_name;

        assertTenantDenied(
            asTenant($actor)->getJson("/api/tools/sgc/companies/{$foreign["company"]->id}")
        );

        assertTenantDenied(
            asTenant($actor)->putJson("/api/tools/sgc/companies/{$foreign["company"]->id}", [
                "legal_name" => "EMPRESA INVADIDA",
            ])
        );

        assertTenantDenied(
            asTenant($actor)->deleteJson("/api/tools/sgc/companies/{$foreign["company"]->id}")
        );

        assertTenantDenied(
            asTenant($actor)->postJson("/api/tools/sgc/companies", [
                "legal_name" => "FILIAL INVADIDA",
                "parent_id" => $foreign["company"]->id,
            ])
        );

        expect($foreign["company"]->fresh()->legal_name)->toBe($original_name)
            ->and($foreign["company"]->fresh()->trashed())->toBeFalse();
    })->with("pares_empresas_distintas");

    it("nao lista, consulta nem altera clientes de outra empresa", function (string $actor, string $target) {
        $own = tenant($actor);
        $foreign = tenant($target);
        $original_name = $foreign["customer"]->name;

        $list = asTenant($actor)->getJson("/api/tools/sgc/customers")->assertOk();

        expect(jsonIds($list, "customers"))
            ->toContain($own["customer"]->id)
            ->and(jsonIds($list, "customers"))->not->toContain($foreign["customer"]->id);

        assertTenantDenied(
            asTenant($actor)->getJson("/api/tools/sgc/customers?company_id={$foreign["company"]->id}")
        );

        assertTenantDenied(
            asTenant($actor)->getJson("/api/tools/sgc/customers/{$foreign["customer"]->id}")
        );

        assertTenantDenied(
            asTenant($actor)->putJson("/api/tools/sgc/customers/{$foreign["customer"]->id}", [
                "company_id" => $foreign["company"]->id,
                "name" => "CLIENTE INVADIDO",
            ])
        );

        assertTenantDenied(
            asTenant($actor)->putJson("/api/tools/sgc/customers/{$foreign["customer"]->id}", [
                "company_id" => $own["company"]->id,
                "name" => "CLIENTE INVADIDO",
            ])
        );

        assertTenantDenied(
            asTenant($actor)->deleteJson("/api/tools/sgc/customers/{$foreign["customer"]->id}", [
                "company_id" => $foreign["company"]->id,
            ])
        );

        expect($foreign["customer"]->fresh()->name)->toBe($original_name)
            ->and($foreign["customer"]->fresh()->trashed())->toBeFalse()
            ->and($foreign["customer"]->companies()->whereKey($foreign["company"]->id)->exists())->toBeTrue();
    })->with("pares_empresas_distintas");

    it("consulta cliente ja cadastrado no sistema pelo CPF, sem expor vinculos de outras empresas", function (string $actor, string $target) {
        $foreign = tenant($target);

        $lookup = asTenant($actor)
            ->getJson("/api/tools/sgc/customers/lookup?tax_id={$foreign["customer"]->tax_id}")
            ->assertOk();

        expect($lookup->json("customer.id"))->toBe($foreign["customer"]->id)
            ->and($lookup->json("customer.name"))->toBe($foreign["customer"]->name)
            ->and($lookup->json("customer.tax_id"))->toBe($foreign["customer"]->tax_id)
            ->and(collect($lookup->json("customer.companies"))->pluck("id")->all())
            ->not->toContain($foreign["company"]->id)
            ->and($lookup->json("customer.addresses") ?? [])->toBeEmpty();
    })->with("pares_empresas_distintas");

    it("vincula cliente ja cadastrado a propria empresa sem duplicar o registro", function (string $actor, string $target) {
        $own = tenant($actor);
        $foreign = tenant($target);

        $response = asTenant($actor)->postJson("/api/tools/sgc/customers", [
            "company_id" => $own["company"]->id,
            "customer_id" => $foreign["customer"]->id,
            "name" => $foreign["customer"]->name,
            "tax_id" => $foreign["customer"]->tax_id,
        ])->assertCreated();

        expect($response->json("customer.id"))->toBe($foreign["customer"]->id)
            ->and($foreign["customer"]->companies()->whereKey($own["company"]->id)->exists())->toBeTrue()
            ->and($foreign["customer"]->companies()->whereKey($foreign["company"]->id)->exists())->toBeTrue();
    })->with("pares_empresas_distintas");

    it("nao consulta nem altera enderecos de cliente de outra empresa", function (string $actor, string $target) {
        $foreign = tenant($target);
        $original_city = $foreign["address"]->address_city;

        assertTenantDenied(
            asTenant($actor)->getJson("/api/tools/sgc/customers/{$foreign["customer"]->id}/addresses")
        );

        assertTenantDenied(
            asTenant($actor)->postJson("/api/tools/sgc/customers/{$foreign["customer"]->id}/addresses", [
                "address_city" => "CIDADE INVADIDA",
            ])
        );

        assertTenantDenied(
            asTenant($actor)->putJson(
                "/api/tools/sgc/customers/{$foreign["customer"]->id}/addresses/{$foreign["address"]->id}",
                ["address_city" => "CIDADE INVADIDA"]
            )
        );

        assertTenantDenied(
            asTenant($actor)->deleteJson(
                "/api/tools/sgc/customers/{$foreign["customer"]->id}/addresses/{$foreign["address"]->id}"
            )
        );

        expect($foreign["address"]->fresh()->address_city)->toBe($original_city)
            ->and($foreign["customer"]->addresses()->count())->toBe(1);
    })->with("pares_empresas_distintas");

    it("nao consulta nem altera anexos de cliente de outra empresa", function (string $actor, string $target) {
        Storage::fake("local");

        $foreign = tenant($target);
        $attachment = makeSgcCustomerAttachment($foreign);

        assertTenantDenied(
            asTenant($actor)->getJson(
                "/api/tools/sgc/customers/{$foreign["customer"]->id}/attachments?company_id={$foreign["company"]->id}"
            )
        );

        assertTenantDenied(
            asTenant($actor)->post("/api/tools/sgc/customers/{$foreign["customer"]->id}/attachments", [
                "company_id" => $foreign["company"]->id,
                "type" => SgcCustomerAttachment::TYPE_CONTRACT,
                "file" => UploadedFile::fake()->image("contrato.jpg"),
            ])
        );

        assertTenantDenied(
            asTenant($actor)->get(
                "/api/tools/sgc/customers/{$foreign["customer"]->id}/attachments/{$attachment->id}"
            )
        );

        assertTenantDenied(
            asTenant($actor)->deleteJson(
                "/api/tools/sgc/customers/{$foreign["customer"]->id}/attachments/{$attachment->id}"
            )
        );

        expect(SgcCustomerAttachment::query()->whereKey($attachment->id)->exists())->toBeTrue();
    })->with("pares_empresas_distintas");

    it("nao expoe anexos da outra empresa apos vincular o mesmo cliente", function (string $actor, string $target) {
        Storage::fake("local");

        $own = tenant($actor);
        $foreign = tenant($target);
        $attachment = makeSgcCustomerAttachment($foreign);

        asTenant($actor)->postJson("/api/tools/sgc/customers", [
            "company_id" => $own["company"]->id,
            "customer_id" => $foreign["customer"]->id,
            "name" => $foreign["customer"]->name,
            "tax_id" => $foreign["customer"]->tax_id,
        ])->assertCreated();

        $list = asTenant($actor)
            ->getJson(
                "/api/tools/sgc/customers/{$foreign["customer"]->id}/attachments?company_id={$own["company"]->id}"
            )
            ->assertOk();

        expect(jsonIds($list, "attachments"))->not->toContain($attachment->id);

        assertTenantDenied(
            asTenant($actor)->get(
                "/api/tools/sgc/customers/{$foreign["customer"]->id}/attachments/{$attachment->id}"
            )
        );
    })->with("pares_empresas_distintas");

    it("consulta e cria anexos da propria empresa", function (string $key) {
        Storage::fake("local");

        $tenant = tenant($key);

        $create = asTenant($key)->post(
            "/api/tools/sgc/customers/{$tenant["customer"]->id}/attachments",
            [
                "company_id" => $tenant["company"]->id,
                "type" => SgcCustomerAttachment::TYPE_CONTRACT,
                "file" => UploadedFile::fake()->image("contrato.jpg"),
            ]
        )->assertCreated();

        $attachment_id = $create->json("attachment.id");

        $list = asTenant($key)
            ->getJson(
                "/api/tools/sgc/customers/{$tenant["customer"]->id}/attachments?company_id={$tenant["company"]->id}"
            )
            ->assertOk();

        expect(jsonIds($list, "attachments"))->toContain($attachment_id)
            ->and($list->json("attachments.0.path") ?? null)->toBeNull();

        asTenant($key)
            ->get("/api/tools/sgc/customers/{$tenant["customer"]->id}/attachments/{$attachment_id}")
            ->assertOk();
    })->with("empresas");

    it("nao lista, consulta nem altera produtos e estoque de outra empresa", function (string $actor, string $target) {
        $own = tenant($actor);
        $foreign = tenant($target);
        $original_name = $foreign["product"]->name;
        $original_stock = (float) $foreign["company_product"]->stock_quantity;
        $original_price = (float) $foreign["company_product"]->sale_price;

        $list = asTenant($actor)->getJson("/api/tools/sgc/products")->assertOk();

        expect(jsonIds($list, "products"))
            ->toContain($own["product"]->id)
            ->and(jsonIds($list, "products"))->not->toContain($foreign["product"]->id);

        expect(collect($list->json("products"))->pluck("sale_price")->map(fn ($value) => (float) $value)->all())
            ->not->toContain($original_price);

        assertTenantDenied(
            asTenant($actor)->getJson("/api/tools/sgc/products?company_id={$foreign["company"]->id}")
        );

        assertTenantDenied(
            asTenant($actor)->getJson("/api/tools/sgc/products/{$foreign["product"]->id}")
        );

        assertTenantDenied(
            asTenant($actor)->getJson(
                "/api/tools/sgc/products/{$foreign["product"]->id}/stock-movements?company_id={$foreign["company"]->id}"
            )
        );

        assertTenantDenied(
            asTenant($actor)->putJson("/api/tools/sgc/products/{$foreign["product"]->id}", [
                "company_id" => $foreign["company"]->id,
                "name" => "PRODUTO INVADIDO",
                "sale_price" => 1,
                "stock_quantity" => 999,
                "stock_movement_reason" => "ajuste indevido",
            ])
        );

        assertTenantDenied(
            asTenant($actor)->deleteJson("/api/tools/sgc/products/{$foreign["product"]->id}", [
                "company_id" => $foreign["company"]->id,
                "stock_movement_reason" => "exclusao indevida",
            ])
        );

        expect($foreign["product"]->fresh()->name)->toBe($original_name)
            ->and((float) $foreign["company_product"]->fresh()->stock_quantity)->toBe($original_stock)
            ->and((float) $foreign["company_product"]->fresh()->sale_price)->toBe($original_price);
    })->with("pares_empresas_distintas");

    it("nao vincula produto de outra empresa nem vazamento de preco/estoque compartilhado", function () {
        $own = tenant("A");
        $foreign = tenant("B");

        $shared = SgcProduct::query()->create([
            "brand_id" => test()->sgcBrand->id,
            "product_category_id" => test()->sgcCategory->id,
            "name" => "PRODUTO COMPARTILHADO",
        ]);

        SgcCompanyProduct::query()->create([
            "company_id" => $own["company"]->id,
            "product_id" => $shared->id,
            "unit_of_measure_id" => test()->sgcUnit->id,
            "sale_price" => 10,
            "cost_price" => 4,
            "stock_quantity" => 5,
            "serial_number" => "SHR-A",
            "currency" => "BRL",
            "notes" => "notas da empresa A",
        ]);

        SgcCompanyProduct::query()->create([
            "company_id" => $foreign["company"]->id,
            "product_id" => $shared->id,
            "unit_of_measure_id" => test()->sgcUnit->id,
            "sale_price" => 999.99,
            "cost_price" => 888.88,
            "stock_quantity" => 777,
            "serial_number" => "SHR-B",
            "currency" => "BRL",
            "notes" => "notas secretas da empresa B",
        ]);

        $list = asTenant("A")->getJson("/api/tools/sgc/products")->assertOk();
        $rows = collect($list->json("products"))->where("id", $shared->id);

        expect($rows->isNotEmpty())->toBeTrue();

        foreach( $rows as $row ) {
            expect((float) $row["sale_price"])->not->toBe(999.99)
                ->and((float) $row["cost_price"])->not->toBe(888.88)
                ->and((float) $row["stock_quantity"])->not->toBe(777.0);
        }

        $show = asTenant("A")->getJson("/api/tools/sgc/products/{$shared->id}")->assertOk();
        $company_ids = collect($show->json("product.companies"))->pluck("id")->all();

        expect($company_ids)->toContain($own["company"]->id)
            ->and($company_ids)->not->toContain($foreign["company"]->id);

        $original_name = $foreign["product"]->name;

        $attach = asTenant("A")->postJson("/api/tools/sgc/products", [
            "company_id" => $own["company"]->id,
            "product_id" => $foreign["product"]->id,
            "name" => "PRODUTO ANEXADO",
            "stock_quantity" => 1,
        ]);

        assertTenantDenied($attach);
        expect($foreign["product"]->fresh()->name)->toBe($original_name);
    });

    it("nao lista, consulta nem altera pedidos de outra empresa", function (string $actor, string $target) {
        $own = tenant($actor);
        $foreign = tenant($target);
        $original_notes = $foreign["draft_order"]->notes;
        $original_status = $foreign["confirmed_order"]->status;
        $original_paid = (float) $foreign["installment"]->paid_amount;

        $list = asTenant($actor)->getJson("/api/tools/sgc/orders")->assertOk();
        $ids = jsonIds($list, "orders");

        expect($ids)->toContain($own["draft_order"]->id)
            ->and($ids)->toContain($own["confirmed_order"]->id)
            ->and($ids)->not->toContain($foreign["draft_order"]->id)
            ->and($ids)->not->toContain($foreign["confirmed_order"]->id);

        assertTenantDenied(
            asTenant($actor)->getJson("/api/tools/sgc/orders?company_id={$foreign["company"]->id}")
        );

        assertTenantDenied(
            asTenant($actor)->getJson("/api/tools/sgc/orders/{$foreign["draft_order"]->id}")
        );

        assertTenantDenied(
            asTenant($actor)->putJson("/api/tools/sgc/orders/{$foreign["draft_order"]->id}", [
                "company_id" => $foreign["company"]->id,
                "customer_id" => $foreign["customer"]->id,
                "status" => SgcOrder::STATUS_DRAFT,
                "notes" => "pedido invadido",
                "items" => [
                    [
                        "product_id" => $foreign["product"]->id,
                        "quantity" => 1,
                        "unit_price" => 1,
                    ],
                ],
            ])
        );

        assertTenantDenied(
            asTenant($actor)->deleteJson("/api/tools/sgc/orders/{$foreign["draft_order"]->id}", [
                "stock_movement_reason" => "exclusao indevida",
            ])
        );

        assertTenantDenied(
            asTenant($actor)->postJson("/api/tools/sgc/orders/{$foreign["confirmed_order"]->id}/cancel", [
                "stock_movement_reason" => "cancelamento indevido",
            ])
        );

        assertTenantDenied(
            asTenant($actor)->postJson(
                "/api/tools/sgc/orders/{$foreign["confirmed_order"]->id}/installments/{$foreign["installment"]->id}/pay",
                ["amount" => 1]
            )
        );

        expect($foreign["draft_order"]->fresh()->notes)->toBe($original_notes)
            ->and($foreign["draft_order"]->fresh()->trashed())->toBeFalse()
            ->and($foreign["confirmed_order"]->fresh()->status)->toBe($original_status)
            ->and((float) $foreign["installment"]->fresh()->paid_amount)->toBe($original_paid);
    })->with("pares_empresas_distintas");

    it("nao cria pedido usando cliente ou produto de outra empresa", function (string $actor, string $target) {
        $own = tenant($actor);
        $foreign = tenant($target);

        assertTenantDenied(
            asTenant($actor)->postJson("/api/tools/sgc/orders", [
                "company_id" => $own["company"]->id,
                "customer_id" => $foreign["customer"]->id,
                "status" => SgcOrder::STATUS_DRAFT,
                "items" => [
                    [
                        "product_id" => $own["product"]->id,
                        "quantity" => 1,
                        "unit_price" => 10,
                    ],
                ],
            ])
        );

        assertTenantDenied(
            asTenant($actor)->postJson("/api/tools/sgc/orders", [
                "company_id" => $own["company"]->id,
                "customer_id" => $own["customer"]->id,
                "status" => SgcOrder::STATUS_DRAFT,
                "items" => [
                    [
                        "product_id" => $foreign["product"]->id,
                        "quantity" => 1,
                        "unit_price" => 10,
                    ],
                ],
            ])
        );

        assertTenantDenied(
            asTenant($actor)->postJson("/api/tools/sgc/orders", [
                "company_id" => $foreign["company"]->id,
                "customer_id" => $foreign["customer"]->id,
                "status" => SgcOrder::STATUS_DRAFT,
                "items" => [
                    [
                        "product_id" => $foreign["product"]->id,
                        "quantity" => 1,
                        "unit_price" => 10,
                    ],
                ],
            ])
        );
    })->with("pares_empresas_distintas");

    it("nao lista, consulta nem altera funcionarios de outra empresa", function (string $actor, string $target) {
        $own = tenant($actor);
        $foreign = tenant($target);
        $original_role = $foreign["employee"]->role;

        $list = asTenant($actor)->getJson("/api/tools/sgc/employees")->assertOk();

        expect(jsonIds($list, "employees"))
            ->toContain($own["employee"]->id)
            ->and(jsonIds($list, "employees"))->not->toContain($foreign["employee"]->id);

        assertTenantDenied(
            asTenant($actor)->getJson("/api/tools/sgc/employees?company_id={$foreign["company"]->id}")
        );

        assertTenantDenied(
            asTenant($actor)->getJson("/api/tools/sgc/employees/{$foreign["employee"]->id}")
        );

        assertTenantDenied(
            asTenant($actor)->putJson("/api/tools/sgc/employees/{$foreign["employee"]->id}", [
                "company_id" => $foreign["company"]->id,
                "role" => SgcCompany::ROLE_ADMIN,
            ])
        );

        assertTenantDenied(
            asTenant($actor)->deleteJson("/api/tools/sgc/employees/{$foreign["employee"]->id}")
        );

        assertTenantDenied(
            asTenant($actor)->postJson("/api/tools/sgc/employees", [
                "company_id" => $foreign["company"]->id,
                "email" => $own["user"]->email,
                "role" => SgcCompany::ROLE_SELLER,
            ])
        );

        expect($foreign["employee"]->fresh()->role)->toBe($original_role)
            ->and(SgcCompanyUser::query()->whereKey($foreign["employee"]->id)->exists())->toBeTrue();
    })->with("pares_empresas_distintas");
});

describe("excecao: usuario que trabalha em duas empresas", function () {
    beforeEach(function () {
        tenant("C")["company"]->users()->attach(tenant("A")["user"]->id, [
            "role" => SgcCompany::ROLE_ADMIN,
        ]);
    });

    it("consulta e altera registros da segunda empresa em que trabalha", function () {
        $company_c = tenant("C");
        $actor = asTenant("A");

        $companies = $actor->getJson("/api/tools/sgc/companies")->assertOk();

        expect(jsonIds($companies, "companies"))
            ->toContain(tenant("A")["company"]->id)
            ->toContain($company_c["company"]->id)
            ->and(jsonIds($companies, "companies"))->not->toContain(tenant("B")["company"]->id);

        $actor->getJson("/api/tools/sgc/companies/{$company_c["company"]->id}")->assertOk();
        $actor->getJson("/api/tools/sgc/customers/{$company_c["customer"]->id}")->assertOk();
        $actor->getJson("/api/tools/sgc/products/{$company_c["product"]->id}")->assertOk();
        $actor->getJson("/api/tools/sgc/orders/{$company_c["draft_order"]->id}")->assertOk();
        $actor->getJson("/api/tools/sgc/employees/{$company_c["employee"]->id}")->assertOk();

        $actor->putJson("/api/tools/sgc/customers/{$company_c["customer"]->id}", [
            "company_id" => $company_c["company"]->id,
            "name" => "CLIENTE C PELO USUARIO A",
        ])->assertOk();

        $actor->putJson("/api/tools/sgc/products/{$company_c["product"]->id}", [
            "company_id" => $company_c["company"]->id,
            "name" => "PRODUTO C PELO USUARIO A",
            "sale_price" => 42,
        ])->assertOk();

        expect($company_c["customer"]->fresh()->name)->toBe("CLIENTE C PELO USUARIO A")
            ->and($company_c["product"]->fresh()->name)->toBe("PRODUTO C PELO USUARIO A");
    });

    it("continua sem acesso a empresa em que nao trabalha", function () {
        $company_b = tenant("B");

        assertTenantDenied(
            asTenant("A")->getJson("/api/tools/sgc/companies/{$company_b["company"]->id}")
        );
        assertTenantDenied(
            asTenant("A")->getJson("/api/tools/sgc/customers/{$company_b["customer"]->id}")
        );
        assertTenantDenied(
            asTenant("A")->getJson("/api/tools/sgc/products/{$company_b["product"]->id}")
        );
        assertTenantDenied(
            asTenant("A")->getJson("/api/tools/sgc/orders/{$company_b["draft_order"]->id}")
        );
        assertTenantDenied(
            asTenant("A")->putJson("/api/tools/sgc/companies/{$company_b["company"]->id}", [
                "legal_name" => "EMPRESA B INVADIDA",
            ])
        );
    });
});

function seedSgcCatalog(): void
{
    test()->sgcBrand = SgcBrand::query()->create([
        "name" => "MARCA TESTE",
    ]);

    test()->sgcCategory = SgcProductCategory::query()->create([
        "name" => "CATEGORIA TESTE",
    ]);

    test()->sgcUnit = SgcUnitOfMeasure::query()->create([
        "abbreviation" => "UN",
        "name" => "UNIDADE",
    ]);

    test()->sgcPaymentMethod = SgcPaymentMethod::query()->create([
        "slug" => SgcPaymentMethod::CASH,
        "name" => "Dinheiro",
        "surcharge_percentage" => 0,
    ]);
}

/**
 * @return array{
 *     user: User,
 *     company: SgcCompany,
 *     employee: SgcCompanyUser,
 *     customer: SgcCustomer,
 *     address: \App\Models\SgcCustomerAddress,
 *     product: SgcProduct,
 *     company_product: SgcCompanyProduct,
 *     draft_order: SgcOrder,
 *     confirmed_order: SgcOrder,
 *     installment: SgcOrderInstallment
 * }
 */
function makeSgcTenant(string $key, string $tax_id, string $identity_document): array
{
    $user = User::factory()->create([
        "name" => "Usuario {$key}",
        "email" => "usuario-{$key}@sgc.test",
    ]);

    $seller = User::factory()->create([
        "name" => "Vendedor {$key}",
        "email" => "vendedor-{$key}@sgc.test",
    ]);

    $company = SgcCompany::query()->create([
        "owner_id" => $user->id,
        "legal_name" => "EMPRESA {$key} LTDA",
        "trade_name" => "EMPRESA {$key}",
        "document" => $tax_id,
    ]);

    $company->users()->attach($user->id, [
        "role" => SgcCompany::ROLE_ADMIN,
    ]);

    $employee = SgcCompanyUser::query()->create([
        "company_id" => $company->id,
        "user_id" => $seller->id,
        "role" => SgcCompany::ROLE_SELLER,
    ]);

    $customer = SgcCustomer::query()->create([
        "name" => "CLIENTE EMPRESA {$key}",
        "nickname" => "CLIENTE {$key}",
        "identity_document" => $identity_document,
        "tax_id" => $tax_id,
    ]);

    $customer->companies()->attach($company->id, [
        "notes" => "notas internas da empresa {$key}",
    ]);

    $address = $customer->addresses()->create([
        "address_street" => "Rua {$key}",
        "address_number" => "10",
        "address_city" => "Cidade {$key}",
        "address_state" => "AM",
        "address_postal_code" => "69000000",
        "address_country" => "BR",
    ]);

    $sale_price = match ($key) {
        "A" => 11.11,
        "B" => 22.22,
        default => 33.33,
    };

    $product = SgcProduct::query()->create([
        "brand_id" => test()->sgcBrand->id,
        "product_category_id" => test()->sgcCategory->id,
        "name" => "PRODUTO EMPRESA {$key}",
    ]);

    $company_product = SgcCompanyProduct::query()->create([
        "company_id" => $company->id,
        "product_id" => $product->id,
        "unit_of_measure_id" => test()->sgcUnit->id,
        "sale_price" => $sale_price,
        "cost_price" => $sale_price / 2,
        "stock_quantity" => 10,
        "serial_number" => "SN-{$key}",
        "currency" => "BRL",
        "notes" => "estoque da empresa {$key}",
    ]);

    $draft_order = SgcOrder::query()->create([
        "company_id" => $company->id,
        "customer_id" => $customer->id,
        "created_by_id" => $user->id,
        "customer_address_id" => $address->id,
        "payment_method_id" => test()->sgcPaymentMethod->id,
        "uuid" => (string) Str::uuid(),
        "status" => SgcOrder::STATUS_DRAFT,
        "discount_amount" => 0,
        "total" => $sale_price,
        "currency" => "BRL",
        "notes" => "rascunho da empresa {$key}",
    ]);

    $draft_order->items()->create([
        "product_id" => $product->id,
        "unit_of_measure_id" => test()->sgcUnit->id,
        "quantity" => 1,
        "unit_price" => $sale_price,
        "discount_amount" => 0,
    ]);

    $confirmed_order = SgcOrder::query()->create([
        "company_id" => $company->id,
        "customer_id" => $customer->id,
        "created_by_id" => $user->id,
        "customer_address_id" => $address->id,
        "payment_method_id" => test()->sgcPaymentMethod->id,
        "uuid" => (string) Str::uuid(),
        "status" => SgcOrder::STATUS_CONFIRMED,
        "discount_amount" => 0,
        "total" => $sale_price,
        "currency" => "BRL",
        "notes" => "pedido confirmado da empresa {$key}",
        "ordered_at" => now(),
    ]);

    $confirmed_order->items()->create([
        "product_id" => $product->id,
        "unit_of_measure_id" => test()->sgcUnit->id,
        "quantity" => 1,
        "unit_price" => $sale_price,
        "discount_amount" => 0,
    ]);

    $installment = $confirmed_order->installments()->create([
        "installment_number" => 1,
        "due_date" => now()->addMonth()->toDateString(),
        "amount" => $sale_price,
        "paid_amount" => 0,
        "paid_at" => null,
    ]);

    return [
        "user" => $user,
        "company" => $company,
        "employee" => $employee,
        "customer" => $customer,
        "address" => $address,
        "product" => $product,
        "company_product" => $company_product,
        "draft_order" => $draft_order,
        "confirmed_order" => $confirmed_order,
        "installment" => $installment,
    ];
}

function makeSgcCustomerAttachment(array $tenant): SgcCustomerAttachment
{
    $pivot = SgcCompanyCustomer::query()
        ->where("company_id", $tenant["company"]->id)
        ->where("customer_id", $tenant["customer"]->id)
        ->firstOrFail();

    $path = "sgc/customers/{$tenant["customer"]->id}/attachments/contrato-{$tenant["company"]->id}.pdf";

    Storage::disk("local")->put($path, "conteudo");

    return $pivot->attachments()->create([
        "type" => SgcCustomerAttachment::TYPE_CONTRACT,
        "original_name" => "contrato.pdf",
        "path" => $path,
        "mime_type" => "application/pdf",
        "size_bytes" => 8,
    ]);
}

function tenant(string $key): array
{
    return test()->tenants[$key];
}

function asTenant(string $key)
{
    return test()->actingAs(tenant($key)["user"]);
}

function jsonIds(TestResponse $response, string $key): array
{
    return collect($response->json($key))->pluck("id")->map(fn ($id) => (int) $id)->all();
}

function assertTenantDenied(TestResponse $response): void
{
    expect($response->status())->toBeIn([403, 404, 422]);
}
