# Fase 1 — Empresas, Membros e Contexto

**Referência:** [schema.md](schema.md) · [conventions.md](conventions.md)

## Objetivo

Entregar a primeira UI do SGC: CRUD de empresas, gestão de membros, seleção de tenant/empresa na sessão, e entrada do módulo em `tools.js`. Ao final, o tenant do usuário existe no Postgres com ao menos uma empresa.

## Pré-requisitos

- **Fase 0 concluída.**
- Verificar: models `Tenant`, `Company`, `TenantMember` existem; middleware `tenant` registrado; `SGCContext` e traits funcionais.

## Escopo

### Backend — Empresas (Postgres)

Controller: `app/Http/Controllers/SGC/CompanyController.php`

| Método | Rota | Nome | Auth |
|--------|------|------|------|
| GET | `/api/sgc/companies` | `api.sgc.companies.index` | auth |
| POST | `/api/sgc/companies` | `api.sgc.companies.store` | auth |
| PUT | `/api/sgc/companies/{company}` | `api.sgc.companies.update` | auth + tenant |
| DELETE | `/api/sgc/companies/{company}` | `api.sgc.companies.destroy` | auth + tenant |

Form Requests: `StoreCompanyRequest`, `UpdateCompanyRequest` em `app/Http/Requests/SGC/Company/`.

**Regras:**
- Ao criar a **primeira empresa**, se o usuário não tem tenant:
  1. Criar `Tenant` com `owner_id = user.id`.
  2. Criar `TenantMember` com `role = admin` para o owner.
  3. Criar a `Company` vinculada ao tenant.
- Respeitar `TenantLimit::companies()`.
- Normalizar CNPJ/CEP para só dígitos em `prepareForValidation`.
- Apenas `admin` pode criar/editar/excluir empresas.
- **Excluir empresa:** bloquear se existirem registros de negócio vinculados (clientes, produtos, pedidos). Retornar 422 com mensagem clara. Evita cascade destrutivo via FKs reais.

### Backend — Membros (Postgres)

Controller: `app/Http/Controllers/SGC/TenantMemberController.php`

| Método | Rota | Nome | Auth |
|--------|------|------|------|
| GET | `/api/sgc/members` | `api.sgc.members.index` | auth + tenant |
| POST | `/api/sgc/members` | `api.sgc.members.store` | auth + tenant |
| PUT | `/api/sgc/members/{member}` | `api.sgc.members.update` | auth + tenant |
| DELETE | `/api/sgc/members/{member}` | `api.sgc.members.destroy` | auth + tenant |

Form Requests: `StoreTenantMemberRequest`, `UpdateTenantMemberRequest`.

**Regras:**
- Adicionar membro por **e-mail** de usuário já registrado no site.
- Se e-mail não existe: erro `"Usuário não encontrado. Peça para se cadastrar primeiro."`
- Apenas `admin` pode gerenciar membros.
- Respeitar `TenantLimit::members()`.
- `seller` pode ter `company_id` para restringir a uma empresa.
- Não permitir remover o owner do tenant.

### Backend — Contexto (sessão)

Controller: `app/Http/Controllers/SGC/ContextController.php`

| Método | Rota | Nome | Auth |
|--------|------|------|------|
| GET | `/api/sgc/context` | `api.sgc.context.show` | auth |
| POST | `/api/sgc/context` | `api.sgc.context.store` | auth |

**GET /api/sgc/context** — retorna:

```json
{
  "tenants": [
    {
      "id": 1,
      "companies": [{ "id": 1, "trade_name": "Loja Centro" }],
      "role": "admin"
    }
  ],
  "active_tenant_id": 1,
  "active_company_id": 1,
  "active_company": { "id": 1, "trade_name": "...", "city": "...", "state": "...", "country": "BR" }
}
```

**POST /api/sgc/context** — body: `{ "tenant_id": 1, "company_id": 1 }`
- Valida membership.
- Valida que company pertence ao tenant.
- Se seller com `company_id` fixo, ignora company_id do body e usa o fixo.
- Grava `sgc_tenant_id` e `sgc_company_id` na sessão.

### Rotas web

Em `routes/web.php`:

```php
Route::middleware("auth")->prefix("sgc")->group(function () {
    Route::inertia("/", "SGC/Index")->name("sgc");
    Route::inertia("/companies", "SGC/Companies")->name("sgc.companies");
    Route::inertia("/members", "SGC/Members")->name("sgc.members");
});
```

### Frontend

**Entrada em tools.js:**

```js
{
    key: "sgc",
    title: "SGC",
    subtitle: "Sistema de Gestão Comercial",
    icon: "mdi-store",
    href: route("sgc"),
    features: ["Empresas", "Clientes", "Estoque", "Vendas"],
}
```

**Pages:**

- `resources/js/Pages/SGC/Index.vue`
  - Se usuário não tem tenant/empresa selecionada: exibir seletor.
  - Se tem: exibir resumo (nome empresa, atalhos para módulos futuros).
  - Chamar `POST /api/sgc/context` ao selecionar.

- `resources/js/Pages/SGC/Companies.vue`
  - `VirtualRemoteTable` + `CompanyFormFields` (BottomSheet).
  - CRUD completo.

- `resources/js/Pages/SGC/Members.vue`
  - `VirtualRemoteTable` + `MemberFormFields` (BottomSheet).
  - Campo e-mail + select role + select company (opcional).

**Components:**

- `resources/js/Components/SGC/CompanyFormFields.vue`
- `resources/js/Components/SGC/MemberFormFields.vue`

## Fora do escopo

- Clientes, produtos, estoque, vendas (Fases 2–5).
- Convite por e-mail com link de aceite (MVP: usuário deve estar cadastrado).
- Filiais (`parent_id`) — coluna existe, sem UI.
- Assinaturas/planos pagos.

## Arquivos a criar/editar

| Ação | Caminho |
|------|---------|
| criar | `app/Http/Controllers/SGC/CompanyController.php` |
| criar | `app/Http/Controllers/SGC/TenantMemberController.php` |
| criar | `app/Http/Controllers/SGC/ContextController.php` |
| criar | `app/Http/Requests/SGC/Company/StoreCompanyRequest.php` |
| criar | `app/Http/Requests/SGC/Company/UpdateCompanyRequest.php` |
| criar | `app/Http/Requests/SGC/Member/StoreTenantMemberRequest.php` |
| criar | `app/Http/Requests/SGC/Member/UpdateTenantMemberRequest.php` |
| criar | `resources/js/Pages/SGC/Index.vue` |
| criar | `resources/js/Pages/SGC/Companies.vue` |
| criar | `resources/js/Pages/SGC/Members.vue` |
| criar | `resources/js/Components/SGC/CompanyFormFields.vue` |
| criar | `resources/js/Components/SGC/MemberFormFields.vue` |
| editar | `routes/api.php` (grupo SGC) |
| editar | `routes/web.php` (grupo SGC) |
| editar | `resources/js/Data/tools.js` |

## Schema desta fase

Tabelas: `sgc_tenants`, `sgc_companies`, `sgc_tenant_members` — ver [schema.md](schema.md#controle).

## Regras de negócio

1. Primeira empresa do usuário cria tenant + member admin + company (nesta ordem).
2. Owner nunca pode ser removido de `sgc_tenant_members`.
3. Seller com `company_id` só vê/acessa aquela empresa.
4. Contexto na sessão é obrigatório antes de acessar rotas com middleware `tenant`.
5. Exclusão de empresa bloqueada se houver dados de negócio vinculados.

## Contrato de API

### GET /api/sgc/companies

Query: `per_page` (default 15, max 100).

Response 200:

```json
{
  "current_page": 1,
  "last_page": 1,
  "per_page": 15,
  "total": 1,
  "companies": [{ "id": 1, "legal_name": "...", "trade_name": "...", ... }]
}
```

### POST /api/sgc/companies

Body:

```json
{
  "legal_name": "Razão Social LTDA",
  "trade_name": "Loja Centro",
  "document": "12345678000199",
  "street": "Rua A",
  "number": "100",
  "district": "Centro",
  "city": "São Paulo",
  "state": "SP",
  "zip_code": "01001000",
  "country": "BR"
}
```

Response 201: `{ "company": { ... } }`

### POST /api/sgc/members

Body:

```json
{
  "email": "vendedor@email.com",
  "role": "seller",
  "company_id": 1
}
```

Response 201: `{ "member": { "id": 2, "user_id": 5, "role": "seller", ... } }`

## Frontend

- Layout: `Container` com `gapped`.
- Listas: padrão `Posts.vue` (SearchField + VirtualRemoteTable + v-fab).
- Forms: `BottomSheet` com `defineExpose({ open })`.
- Index: cards ou lista para seleção de tenant/empresa; após seleção, redirecionar ou mostrar dashboard vazio.

## Critérios de aceite

- [ ] Ferramenta SGC aparece em `/tools`.
- [ ] Usuário autenticado acessa `/sgc` e vê seletor de empresa.
- [ ] Criar primeira empresa cria tenant e member admin no Postgres.
- [ ] CRUD de empresas funciona (listar, criar, editar, excluir).
- [ ] Exclusão de empresa com dados vinculados retorna erro 422.
- [ ] Admin adiciona membro por e-mail de usuário existente.
- [ ] Erro claro se e-mail não cadastrado.
- [ ] Seller restrito à empresa configurada.
- [ ] Contexto persiste na sessão entre requests.
- [ ] Rotas com middleware `tenant` funcionam após seleção de contexto.

## Pegadinhas

- **Ordem de criação:** tenant → member → company. Não inverter.
- **Index de companies sem tenant:** listar empresas do tenant do usuário (owner ou member), não exigir middleware `tenant` no index se ainda não há contexto selecionado.
- **Cascade de FKs:** excluir empresa com clientes/produtos dispara cascade destrutivo — validar antes no controller.
- **NavigationDrawer:** considerar adicionar link SGC no menu (opcional nesta fase).
