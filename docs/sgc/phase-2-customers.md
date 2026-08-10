# Fase 2 — Clientes

**Referência:** [schema.md](schema.md) · [conventions.md](conventions.md)

## Objetivo

CRUD de clientes e endereços no Postgres, com isolamento por tenant e empresa via traits da Fase 0 e pré-preenchimento de endereço a partir da empresa ativa.

## Pré-requisitos

- **Fase 1 concluída.**
- Verificar: contexto (tenant + empresa) selecionado na sessão; middleware `tenant` funcional; traits `BelongsToTenant` e `BelongsToCompany` existem.

## Escopo

### Migration

- `database/migrations/xxxx_create_sgc_customers_table.php`
  - Tabelas: `sgc_customers`, `sgc_customer_addresses` (ver [schema.md](schema.md#sgc_customers)).
  - FKs reais para `sgc_tenants`, `sgc_companies`, `sgc_customers`.
  - Índices: `index(tenant_id)`, `index(tenant_id, company_id)`.
  - (Recomendado) RLS conforme [phase-0-tenant-infra.md](phase-0-tenant-infra.md#rls-e-testes-de-isolamento-recomendado).

Rodar: `php artisan migrate`.

### Models

- `app/Models/SGC/Customer.php` — usa `BelongsToTenant`, `BelongsToCompany`, `hasMany CustomerAddress`.
- `app/Models/SGC/CustomerAddress.php` — usa `BelongsToTenant`, `belongsTo Customer`.

Constantes:

```php
public const ADDRESS_RESIDENTIAL = "residential";
public const ADDRESS_COMMERCIAL = "commercial";
```

### Backend

Controller: `app/Http/Controllers/SGC/CustomerController.php`

| Método | Rota | Nome | Auth |
|--------|------|------|------|
| GET | `/api/sgc/customers` | `api.sgc.customers.index` | auth + tenant |
| GET | `/api/sgc/customers/{customer}` | `api.sgc.customers.show` | auth + tenant |
| POST | `/api/sgc/customers` | `api.sgc.customers.store` | auth + tenant |
| PUT | `/api/sgc/customers/{customer}` | `api.sgc.customers.update` | auth + tenant |
| DELETE | `/api/sgc/customers/{customer}` | `api.sgc.customers.destroy` | auth + tenant |

Form Requests: `StoreCustomerRequest`, `UpdateCustomerRequest`.

**Regras:**
- CPF normalizado para só dígitos; unique por `(company_id, document)`.
- Endereços aninhados no store/update (array `addresses`); cada endereço recebe `tenant_id` do contexto.
- Ao menos um endereço opcional no MVP.
- `authorize()`: qualquer membro do tenant (`admin` ou `seller`).
- Respeitar `TenantLimit::records()` antes de criar.

### Rotas web

```php
Route::inertia("/customers", "SGC/Customers")->name("sgc.customers");
```

### Frontend

- `resources/js/Pages/SGC/Customers.vue` — lista com VirtualRemoteTable.
- `resources/js/Components/SGC/CustomerFormFields.vue` — Dialog ou BottomSheet.

**Pré-preenchimento de endereço:**

Ao adicionar endereço, preencher automaticamente a partir de `active_company` (vindo de `/api/sgc/context` ou prop Inertia):

```js
const defaultAddress = {
    city: activeCompany.value?.city ?? "",
    state: activeCompany.value?.state ?? "",
    country: activeCompany.value?.country ?? "BR",
    type: "residential",
};
```

Usuário pode editar antes de salvar.

**Campos do formulário:**

- Cliente: name, document (CPF), rg, birth_date, nickname, phone, email, notes.
- Endereços (repetível): type, street, number, district, city, state, zip_code, country, notes.

## Fora do escopo

- Produtos, estoque, vendas (Fases 3–5).
- Validação de CPF com dígito verificador (opcional; MVP aceita 11 dígitos).
- Criptografia de CPF (documentado em schema.md como decisão futura).
- Busca por CEP automática (ViaCEP).

## Arquivos a criar/editar

| Ação | Caminho |
|------|---------|
| criar | `database/migrations/xxxx_create_sgc_customers_table.php` |
| criar | `app/Models/SGC/Customer.php` |
| criar | `app/Models/SGC/CustomerAddress.php` |
| criar | `app/Http/Controllers/SGC/CustomerController.php` |
| criar | `app/Http/Requests/SGC/Customer/StoreCustomerRequest.php` |
| criar | `app/Http/Requests/SGC/Customer/UpdateCustomerRequest.php` |
| criar | `resources/js/Pages/SGC/Customers.vue` |
| criar | `resources/js/Components/SGC/CustomerFormFields.vue` |
| editar | `routes/api.php` |
| editar | `routes/web.php` |

## Schema desta fase

- `sgc_customers` — [schema.md](schema.md#sgc_customers)
- `sgc_customer_addresses` — [schema.md](schema.md#sgc_customer_addresses)

## Regras de negócio

1. Global scopes garantem que usuário só vê clientes do tenant e empresa ativos.
2. CPF unique por empresa; mesmo CPF pode existir em empresas diferentes do mesmo tenant.
3. Excluir cliente cascadeia endereços.
4. Endereço pré-preenchido com cidade/estado/país da empresa; editável.

## Contrato de API

### POST /api/sgc/customers

Body:

```json
{
  "name": "João Silva",
  "document": "12345678901",
  "rg": "1234567",
  "birth_date": "1990-05-15",
  "nickname": "João",
  "phone": "11999998888",
  "email": "joao@email.com",
  "notes": "",
  "addresses": [
    {
      "type": "residential",
      "street": "Rua B",
      "number": "50",
      "district": "Jardim",
      "city": "São Paulo",
      "state": "SP",
      "zip_code": "01002000",
      "country": "BR"
    }
  ]
}
```

Response 201: `{ "customer": { "id": 1, "addresses": [...] } }`

### GET /api/sgc/customers/{id}

Response 200: `{ "customer": { ..., "addresses": [...] } }`

## Frontend

- Busca por nome, CPF, apelido, telefone.
- Form com seção de endereços repetível (adicionar/remover).
- Máscara de CPF/telefone no front (opcional; backend normaliza).

## Critérios de aceite

- [ ] Migration cria `sgc_customers` e `sgc_customer_addresses` com FKs reais.
- [ ] Traits filtram por tenant e empresa ativos.
- [ ] CRUD de clientes funciona via API.
- [ ] Endereços salvos/atualizados junto com cliente (com `tenant_id`).
- [ ] CPF duplicado na mesma empresa retorna erro de validação.
- [ ] Pré-preenchimento de cidade/estado/país funciona no form.
- [ ] Página `/sgc/customers` lista e permite criar/editar/excluir.
- [ ] Seller consegue gerenciar clientes da empresa permitida.

## Pegadinhas

- **Route model binding:** o binding respeita global scopes — customer de outro tenant/empresa retorna 404.
- **Global scope em show/update:** validar explicitamente no authorize além do scope.
- **Sync de endereços no update:** MVP: delete all + recreate; propagar `tenant_id` em cada endereço novo.
- **RLS:** se ativo, lembrar de `SET LOCAL app.tenant_id` dentro de transações.
