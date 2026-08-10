# Fase 0 — Infra de Tenant

**Referência:** [schema.md](schema.md) · [conventions.md](conventions.md)

## Objetivo

Criar a fundação do módulo SGC: tabelas de controle no Postgres, contexto de tenant/empresa via helper e traits, middleware de resolução e limites por cota — sem UI.

## Pré-requisitos

- Nenhum (primeira fase).
- Verificar: Laravel 13 rodando, Postgres configurado em produção.

## Escopo

### Postgres — tabelas de controle

- Migration `database/migrations/xxxx_create_sgc_tenant_tables.php` com `sgc_tenants`, `sgc_companies`, `sgc_tenant_members` (ver [schema.md](schema.md#controle)).
- Models: `app/Models/SGC/Tenant.php`, `Company.php`, `TenantMember.php`.
- Relacionamentos: `Tenant belongsTo User (owner)`, `hasMany Company`, `hasMany TenantMember`; `Company belongsTo Tenant`; `TenantMember belongsTo Tenant, User, Company`.
- Cada model define `protected $table = "sgc_*"`.

### SGCContext

- `app/Helpers/SGCContext.php` — armazena `tenant_id` e `company_id` do request atual:

```php
class SGCContext
{
    private static ?int $tenantId = null;
    private static ?int $companyId = null;

    public static function set(int $tenantId, int $companyId): void
    {
        self::$tenantId = $tenantId;
        self::$companyId = $companyId;
    }

    public static function tenantId(): ?int
    {
        return self::$tenantId;
    }

    public static function companyId(): ?int
    {
        return self::$companyId;
    }

    public static function forget(): void
    {
        self::$tenantId = null;
        self::$companyId = null;
    }
}
```

### Traits de escopo

- `app/Models/Concerns/BelongsToTenant.php`

```php
trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope("tenant", function (Builder $builder) {
            $tenantId = SGCContext::tenantId();

            if ($tenantId) {
                $builder->where("tenant_id", $tenantId);
            } else {
                $builder->whereRaw("1 = 0"); // sem contexto = nenhum resultado
            }
        });

        static::creating(function (Model $model) {
            if (empty($model->tenant_id)) {
                $model->tenant_id = SGCContext::tenantId();
            }
        });
    }
}
```

- `app/Models/Concerns/BelongsToCompany.php`

```php
trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        static::addGlobalScope("company", function (Builder $builder) {
            $companyId = SGCContext::companyId();

            if ($companyId) {
                $builder->where("company_id", $companyId);
            } else {
                $builder->whereRaw("1 = 0");
            }
        });

        static::creating(function (Model $model) {
            if (empty($model->company_id)) {
                $model->company_id = SGCContext::companyId();
            }
        });
    }
}
```

Tabelas filhas (`sgc_customer_addresses`, `sgc_order_items`, `sgc_product_lots`, `sgc_product_serials`) usam apenas `BelongsToTenant` — o escopo por empresa vem do FK do pai.

### Helper TenantLimit

- `app/Helpers/TenantLimit.php` — no molde de `app/Helpers/UploadLimit.php`:

```php
class TenantLimit
{
    public static function companies(?User $user = null): int
    {
        return 3;
    }

    public static function members(?User $user = null): int
    {
        return 5;
    }

    public static function records(?User $user = null): int
    {
        // cota total de linhas de negócio por tenant (clientes + produtos + pedidos...)
        return 10000;
    }
}
```

Futuro: consultar assinatura do usuário. Checagem de `records()` nos Form Requests das fases 2–5.

### Middleware

- `app/Http/Middleware/ResolveTenantContext.php`
  - Lê `sgc_tenant_id` e `sgc_company_id` da sessão.
  - Valida que o usuário autenticado é membro do tenant (`sgc_tenant_members`).
  - Valida que `company_id` pertence ao tenant (query em `sgc_companies`).
  - Chama `SGCContext::set($tenantId, $companyId)`.
  - Injeta no request: `tenant`, `SGCCompany` (para uso nos controllers).
  - Aborta 401 se não autenticado, 403 se não membro ou empresa inválida.

- Registrar alias em `bootstrap/app.php`:

```php
"tenant" => ResolveTenantContext::class,
```

### RLS e testes de isolamento (recomendado)

Defesa em profundidade além dos global scopes. **Recomendado implementar nesta fase**, mas não bloqueia as fases seguintes se adiado.

**Row Level Security (Postgres):**

Para cada tabela de negócio (criadas nas Fases 2–5), adicionar na migration ou em migration dedicada:

```sql
ALTER TABLE sgc_customers ENABLE ROW LEVEL SECURITY;

CREATE POLICY tenant_isolation ON sgc_customers
    USING (tenant_id = current_setting('app.tenant_id', true)::bigint);
```

Antes de cada transação de negócio, definir o tenant:

```php
DB::statement("SET LOCAL app.tenant_id = ?", [SGCContext::tenantId()]);
```

`SET LOCAL` só vale dentro de transação — usar dentro de `DB::transaction()`.

**Requisitos:**
- Role da aplicação **sem** `BYPASSRLS`.
- Policy idêntica em todas as tabelas com `tenant_id`.

**Testes de isolamento:**

- `tests/Feature/SGC/TenantIsolationTest.php`

Cenários mínimos:
1. Tenant A não lê dados do tenant B (mesma tabela, contexto A).
2. Sem `SGCContext::set()`, query retorna vazio (global scope).
3. Com RLS ativo, query direta com `tenant_id` errado retorna zero linhas.

Marcar testes como `@group SGC-isolation` se quiser rodar separado: `php artisan test --group=SGC-isolation`.

## Fora do escopo

- UI (Fase 1).
- CRUD de empresas/membros (Fase 1).
- Tabelas de negócio (Fases 2–5).
- Entrada em `tools.js` (Fase 1).

## Arquivos a criar/editar

| Ação | Caminho |
|------|---------|
| criar | `database/migrations/xxxx_create_sgc_tenant_tables.php` |
| criar | `app/Models/SGC/Tenant.php` |
| criar | `app/Models/SGC/Company.php` |
| criar | `app/Models/SGC/TenantMember.php` |
| criar | `app/Helpers/SGCContext.php` |
| criar | `app/Helpers/TenantLimit.php` |
| criar | `app/Models/Concerns/BelongsToTenant.php` |
| criar | `app/Models/Concerns/BelongsToCompany.php` |
| criar | `app/Http/Middleware/ResolveTenantContext.php` |
| criar | `tests/Feature/SGC/TenantIsolationTest.php` (recomendado) |
| editar | `bootstrap/app.php` (alias middleware) |

## Regras de negócio

1. Um tenant por `owner_id` no MVP.
2. Middleware aborta 401 se não autenticado, 403 se não membro ou empresa inválida.
3. Sem contexto definido, global scopes retornam zero linhas (nunca "tudo").
4. Jobs futuros devem receber `tenant_id` + `company_id` e chamar `SGCContext::set()` manualmente (sem sessão).

## Contrato de API

Nenhum endpoint nesta fase. Apenas infraestrutura.

## Frontend

Nenhum nesta fase.

## Critérios de aceite

- [ ] Migration Postgres roda sem erro: `php artisan migrate`.
- [ ] Models `Tenant`, `Company`, `TenantMember` existem com `$table` prefixado e relacionamentos corretos.
- [ ] `SGCContext` set/get/forget funciona.
- [ ] Traits `BelongsToTenant` e `BelongsToCompany` aplicam global scope e preenchem no creating.
- [ ] `TenantLimit` retorna limites estáticos (`companies`, `members`, `records`).
- [ ] Middleware `tenant` registrado, valida membership e popula `SGCContext`.
- [ ] (Recomendado) RLS habilitado em ao menos uma tabela de teste.
- [ ] (Recomendado) `TenantIsolationTest` passa.

## Pegadinhas

- **`SET LOCAL` só vale dentro de transação** — chamar antes de queries soltas não funciona.
- **Global scope sem contexto deve barrar, não retornar tudo** — usar `whereRaw("1 = 0")`.
- **Migrations do módulo não podem depender de `sgc_orders` antes da Fase 5** — FKs adiadas conforme [schema.md](schema.md#fks-adiadas-fase-4--fase-5).
- **Role com BYPASSRLS ignora policies** — verificar permissões do usuário Postgres da aplicação.
