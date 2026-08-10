# Schema de Referência — Módulo SGC

Documento único de schema. As fases referenciam este arquivo; não dupliquem definições.

---

## Postgres (banco único)

Conexão padrão (`pgsql` em produção). Todas as tabelas do módulo usam prefixo `sgc_`. Migrations em `database/migrations/` (autodiscovery).

**Isolamento:** toda tabela de negócio inclui `tenant_id` (FK → `sgc_tenants`) e, nas raízes, `company_id` (FK → `sgc_companies`). Filhas também carregam `tenant_id` para RLS uniforme.

**RLS:** recomendado na Fase 0 — ver [phase-0-tenant-infra.md](phase-0-tenant-infra.md#rls-e-testes-de-isolamento-recomendado).

---

## Controle

### sgc_tenants

| Coluna | Tipo | Null | Default | Notas |
|--------|------|------|---------|-------|
| id | bigint PK | não | auto | |
| owner_id | bigint FK → users | não | | cascadeOnDelete |
| status | enum | não | `active` | `active`, `suspended` |
| plan | string(20) | não | `free` | futuro: assinaturas |
| created_at | timestamp | | | |
| updated_at | timestamp | | | |

**Invariantes:**
- Um tenant por `owner_id` no MVP (unique em `owner_id`).

**Índices:** `index(owner_id)` (unique).

### sgc_companies

| Coluna | Tipo | Null | Default | Notas |
|--------|------|------|---------|-------|
| id | bigint PK | não | auto | |
| tenant_id | bigint FK → sgc_tenants | não | | cascadeOnDelete |
| parent_id | bigint FK → sgc_companies | sim | | filial; sem UI no MVP |
| legal_name | string(255) | não | | razão social |
| trade_name | string(255) | não | | nome fantasia |
| document | string(20) | sim | | documento fiscal (CNPJ, VAT, EIN…); normalizar sem máscara |
| street | string(255) | não | | |
| number | string(20) | não | | |
| district | string(100) | não | | bairro |
| city | string(100) | não | | |
| state | string(2) | não | | UF |
| zip_code | string(8) | não | | só dígitos |
| country | string(2) | não | `BR` | |
| created_at | timestamp | | | |
| updated_at | timestamp | | | |

**Índices:** `index(tenant_id)`.

### sgc_tenant_members

| Coluna | Tipo | Null | Default | Notas |
|--------|------|------|---------|-------|
| id | bigint PK | não | auto | |
| tenant_id | bigint FK → sgc_tenants | não | | cascadeOnDelete |
| user_id | bigint FK → users | não | | cascadeOnDelete |
| role | enum | não | | `admin`, `seller` |
| company_id | bigint FK → sgc_companies | sim | | restringe vendedor a uma empresa |
| created_at | timestamp | | | |
| updated_at | timestamp | | | |

**Constraints:**
- unique(`tenant_id`, `user_id`)

**Constantes (model):**
```php
public const ROLE_ADMIN = "admin";
public const ROLE_SELLER = "seller";
```

**Índices:** `index(tenant_id)`, `index(user_id)`.

---

## Negócio

Models em `app/Models/SGC/` com `protected $table = "sgc_*"`. Usam traits `BelongsToTenant` e `BelongsToCompany` (Fase 0).

### sgc_customers

| Coluna | Tipo | Null | Default | Notas |
|--------|------|------|---------|-------|
| id | bigint PK | não | auto | |
| tenant_id | bigint FK → sgc_tenants | não | | cascadeOnDelete |
| company_id | bigint FK → sgc_companies | não | | cascadeOnDelete |
| name | string(255) | não | | nome completo |
| document | string(11) | não | | CPF, só dígitos |
| rg | string(20) | sim | | |
| birth_date | date | sim | | |
| nickname | string(100) | sim | | apelido |
| phone | string(20) | sim | | |
| email | string(255) | sim | | |
| notes | text | sim | | observação |
| created_at | timestamp | | | |
| updated_at | timestamp | | | |

**Constraints:** unique(`company_id`, `document`)

**Índices:** `index(tenant_id)`, `index(tenant_id, company_id)`.

### sgc_customer_addresses

| Coluna | Tipo | Null | Default | Notas |
|--------|------|------|---------|-------|
| id | bigint PK | não | auto | |
| tenant_id | bigint FK → sgc_tenants | não | | cascadeOnDelete |
| customer_id | bigint FK → sgc_customers | não | | cascadeOnDelete |
| type | string(20) | não | | `residential`, `commercial` |
| street | string(255) | não | | |
| number | string(20) | não | | |
| district | string(100) | não | | |
| city | string(100) | não | | |
| state | string(2) | não | | |
| zip_code | string(8) | não | | |
| country | string(2) | não | `BR` | |
| notes | text | sim | | |
| created_at | timestamp | | | |
| updated_at | timestamp | | | |

**Índices:** `index(tenant_id)`, `index(customer_id)`.

### sgc_product_categories

| Coluna | Tipo | Null | Default | Notas |
|--------|------|------|---------|-------|
| id | bigint PK | não | auto | |
| tenant_id | bigint FK → sgc_tenants | não | | cascadeOnDelete |
| company_id | bigint FK → sgc_companies | não | | cascadeOnDelete |
| name | string(100) | não | | |
| created_at | timestamp | | | |
| updated_at | timestamp | | | |

**Constraints:** unique(`company_id`, `name`)

**Índices:** `index(tenant_id)`, `index(tenant_id, company_id)`.

### sgc_product_brands

Mesma estrutura de `sgc_product_categories`.

### sgc_products

| Coluna | Tipo | Null | Default | Notas |
|--------|------|------|---------|-------|
| id | bigint PK | não | auto | |
| tenant_id | bigint FK → sgc_tenants | não | | cascadeOnDelete |
| company_id | bigint FK → sgc_companies | não | | cascadeOnDelete |
| category_id | bigint FK → sgc_product_categories | sim | | nullOnDelete |
| brand_id | bigint FK → sgc_product_brands | sim | | nullOnDelete |
| sku | string(50) | sim | | |
| description | string(255) | não | | |
| sale_price | integer | não | 0 | centavos por unidade de medida |
| photo_path | string(500) | sim | | caminho relativo |
| active | boolean | não | true | |
| tracking | string(10) | não | `none` | `none`, `lot`, `serial` |
| unit | string(5) | não | `un` | unidade de **venda**: `un`, `kg`, `g`, `L`, `ml`, `m`, `cm` |
| package_label | string(20) | sim | | rótulo da embalagem de compra: "Rolo", "Fardo", "Caixa" |
| package_factor | numeric(12,3) | não | 1 | unidades base por embalagem; `CHECK (package_factor > 0)` |
| created_at | timestamp | | | |
| updated_at | timestamp | | | |

**Constraints:** unique(`company_id`, `sku`) quando `sku` preenchido (partial unique ou validação no app).

**Constantes (model):**
```php
public const TRACKING_NONE = "none";   // fungível: arroz, feijão, fita sem controle de rolo
public const TRACKING_LOT = "lot";     // recipiente divisível: leite, rolo de fita, bobina
public const TRACKING_SERIAL = "serial"; // unidade única: IMEI, número de série
```

**Saldo do produto:** `SUM(stock_items.quantity)` — nunca coluna em `sgc_products`.

**Índices:** `index(tenant_id)`, `index(tenant_id, company_id)`.

### sgc_stock_locations

Endereçamento simples e opcional. Lista plana, sem hierarquia.

| Coluna | Tipo | Null | Default | Notas |
|--------|------|------|---------|-------|
| id | bigint PK | não | auto | |
| tenant_id | bigint FK → sgc_tenants | não | | cascadeOnDelete |
| company_id | bigint FK → sgc_companies | não | | cascadeOnDelete |
| code | string(20) | não | | ex.: `A1`, `PRATELEIRA-3` |
| name | string(100) | não | | |
| active | boolean | não | true | |
| created_at | timestamp | | | |
| updated_at | timestamp | | | |

**Constraints:** unique(`company_id`, `code`).

**Índices:** `index(tenant_id)`, `index(tenant_id, company_id)`.

### sgc_product_lots

Identidade de lote — recipiente físico rastreável com quantidade divisível. **Sem saldo** (saldo em `sgc_stock_items`).

| Coluna | Tipo | Null | Default | Notas |
|--------|------|------|---------|-------|
| id | bigint PK | não | auto | |
| tenant_id | bigint FK → sgc_tenants | não | | cascadeOnDelete |
| company_id | bigint FK → sgc_companies | não | | cascadeOnDelete |
| product_id | bigint FK → sgc_products | não | | |
| code | string(50) | não | | nº do lote, rolo, bobina |
| expires_at | date | sim | | opcional; FEFO só quando preenchido |
| notes | text | sim | | |
| created_at | timestamp | | | |
| updated_at | timestamp | | | |

**Constraints:** unique(`product_id`, `code`).

**Índices:** `index(tenant_id)`, `index(tenant_id, company_id)`, `index(product_id)`, índice parcial `(product_id, expires_at) WHERE expires_at IS NOT NULL`.

### sgc_product_serials

Identidade de unidade serializada (IMEI, nº de série). **Sem saldo** (saldo em `sgc_stock_items`).

| Coluna | Tipo | Null | Default | Notas |
|--------|------|------|---------|-------|
| id | bigint PK | não | auto | |
| tenant_id | bigint FK → sgc_tenants | não | | cascadeOnDelete |
| company_id | bigint FK → sgc_companies | não | | cascadeOnDelete |
| product_id | bigint FK → sgc_products | não | | |
| code | string(50) | não | | IMEI ou nº de série |
| attributes | jsonb | sim | | ex.: `{ "imei2": "...", "color": "preto" }` |
| notes | text | sim | | |
| created_at | timestamp | | | |
| updated_at | timestamp | | | |

**Constraints:** unique(`product_id`, `code`).

**Índices:** `index(tenant_id)`, `index(tenant_id, company_id)`, `index(product_id)`, `index(code)`.

### sgc_stock_items

Saldo de estoque — **única tabela de estoque que sofre UPDATE**. Uma linha por combinação produto + lote/serial + endereço.

| Coluna | Tipo | Null | Default | Notas |
|--------|------|------|---------|-------|
| id | bigint PK | não | auto | |
| tenant_id | bigint FK → sgc_tenants | não | | cascadeOnDelete |
| company_id | bigint FK → sgc_companies | não | | cascadeOnDelete |
| product_id | bigint FK → sgc_products | não | | |
| lot_id | bigint FK → sgc_product_lots | sim | | |
| serial_id | bigint FK → sgc_product_serials | sim | | |
| location_id | bigint FK → sgc_stock_locations | sim | | endereço opcional |
| quantity | numeric(12,3) | não | 0 | saldo físico |
| quantity_reserved | numeric(12,3) | não | 0 | reserva futura; não usado no MVP |
| average_cost | integer | não | 0 | centavos por unidade de medida do produto |
| created_at | timestamp | | | |
| updated_at | timestamp | | | |

**Constraints:**
```sql
UNIQUE NULLS NOT DISTINCT (product_id, lot_id, serial_id, location_id)
CHECK (num_nonnulls(lot_id, serial_id) <= 1)
CHECK (quantity >= 0)
CHECK (quantity_reserved >= 0 AND quantity_reserved <= quantity)
CHECK (serial_id IS NULL OR quantity <= 1)
```

**Modos de rastreio em `sgc_stock_items`:**

| `tracking` | Linhas | `quantity` |
|------------|--------|------------|
| `none` | 1 por produto (× endereço) | qualquer ≥ 0 |
| `lot` | 1 por lote (× endereço) | saldo do lote/rolo |
| `serial` | 1 por aparelho | 0 ou 1 |

**Invariante de saldo do produto:**
```
SUM(stock_items.quantity WHERE product_id = ?) = saldo total do produto
```

**Índices:** `index(tenant_id)`, `index(tenant_id, company_id)`, `index(product_id)`, `index(lot_id)`, `index(serial_id)`, `index(location_id)`, parcial `(product_id, quantity) WHERE quantity > 0`.

### sgc_stock_movements

Kardex append-only. **Nunca** fazer UPDATE ou DELETE.

| Coluna | Tipo | Null | Default | Notas |
|--------|------|------|---------|-------|
| id | bigint PK | não | auto | |
| tenant_id | bigint FK → sgc_tenants | não | | cascadeOnDelete |
| company_id | bigint FK → sgc_companies | não | | cascadeOnDelete |
| stock_item_id | bigint FK → sgc_stock_items | não | | linha de saldo alterada |
| product_id | bigint FK → sgc_products | não | | denormalizado para extrato |
| type | string(10) | não | | `in`, `out` |
| reason | string(20) | não | | ver constantes abaixo |
| quantity | numeric(12,3) | não | | sempre positivo |
| unit_cost | integer | sim | | centavos; obrigatório em `in` |
| order_item_id | bigint | sim | | FK → sgc_order_items adicionada na Fase 5 |
| notes | text | sim | | |
| occurred_at | timestamp | não | | data do movimento |
| created_at | timestamp | | | |
| updated_at | timestamp | | | |

**Constantes (model):**
```php
public const TYPE_IN = "in";
public const TYPE_OUT = "out";

public const REASON_PURCHASE = "purchase";
public const REASON_SALE = "sale";
public const REASON_RETURN = "return";
public const REASON_DEFECT = "defect";
public const REASON_LOSS = "loss";
public const REASON_EXPIRY = "expiry";
public const REASON_INVENTORY = "inventory";
public const REASON_TRANSFER = "transfer";
```

**Constraints:**
```sql
CHECK (quantity > 0)
CHECK (type <> 'in' OR unit_cost IS NOT NULL)
```

**Invariante de reconciliação** (detectável via `php artisan sgc:stock:reconcile`):
```
stock_items.quantity = SUM(CASE type WHEN 'in' THEN quantity ELSE -quantity END)
                       FROM stock_movements WHERE stock_item_id = ?
```

**Índices:** `index(tenant_id)`, `index(tenant_id, company_id)`, `index(stock_item_id)`, `index(product_id)`, `index(order_item_id)`, `index(occurred_at)`.

**Endurecimento opcional (fora do MVP):** trigger no INSERT de movimentos que aplica o delta em `sgc_stock_items`.

### Modos de rastreio (`products.tracking`)

| Modo | Exemplo | Saldo | Custo |
|------|---------|-------|-------|
| `none` | Arroz, feijão, fita fungível | `SUM(quantity)` em `sgc_stock_items` | Média ponderada da linha de saldo |
| `lot` | Leite, rolo de fita, bobina | 1 linha por lote em `sgc_stock_items` | Custo da linha do lote; baixa FEFO se `expires_at` |
| `serial` | Celular, notebook | 1 linha por serial, `quantity` 0 ou 1 | Custo da linha do aparelho |

### sgc_orders

| Coluna | Tipo | Null | Default | Notas |
|--------|------|------|---------|-------|
| id | bigint PK | não | auto | |
| tenant_id | bigint FK → sgc_tenants | não | | cascadeOnDelete |
| company_id | bigint FK → sgc_companies | não | | cascadeOnDelete |
| customer_id | bigint FK → sgc_customers | não | | |
| status | string(20) | não | `draft` | `draft`, `confirmed`, `canceled` |
| payment_method | string(30) | não | | ex.: `cash`, `credit`, `pix` |
| installments | integer | não | 1 | |
| discount_amount | integer | não | 0 | centavos, desconto total |
| total_amount | integer | não | 0 | centavos |
| ordered_at | timestamp | não | | data da compra |
| created_at | timestamp | | | |
| updated_at | timestamp | | | |

**Índices:** `index(tenant_id)`, `index(tenant_id, company_id)`, `index(customer_id)`.

### sgc_order_items

| Coluna | Tipo | Null | Default | Notas |
|--------|------|------|---------|-------|
| id | bigint PK | não | auto | |
| tenant_id | bigint FK → sgc_tenants | não | | cascadeOnDelete |
| order_id | bigint FK → sgc_orders | não | | cascadeOnDelete |
| product_id | bigint FK → sgc_products | não | | |
| quantity | numeric(12,3) | não | | na unidade de medida do produto |
| unit_price | integer | não | | centavos, preço de venda |
| discount_amount | integer | não | 0 | centavos, desconto do item |
| unit_cost | integer | não | | centavos, snapshot do custo na venda |
| created_at | timestamp | | | |
| updated_at | timestamp | | | |

Relação com estoque rastreável via `sgc_stock_movements.order_item_id`:

- **`tracking = serial`:** N movimentos `out` com `reason = sale`, um por `stock_item` (serial) vendido.
- **`tracking = lot`:** um ou mais movimentos `out` por item, consumo parcial permitido via alocação por lote.

**Índices:** `index(tenant_id)`, `index(order_id)`, `index(product_id)`.

---

## FKs adiadas (Fase 4 → Fase 5)

Criadas na Fase 4 como `bigint` nullable **sem** constraint (tabela destino ainda não existe):

| Tabela | Coluna | FK adicionada na Fase 5 |
|--------|--------|-------------------------|
| sgc_stock_movements | order_item_id | → sgc_order_items (nullOnDelete) |

A migration da Fase 5 deve incluir `Schema::table(...)` para adicionar essa constraint após criar `sgc_order_items`.

---

## Invariantes globais

1. **Centavos:** todo valor monetário é `integer` (centavos). Nunca `float`.
2. **Quantidade:** `numeric(12,3)` em saldo, movimentos e itens de pedido. Permite venda fracionada (ex.: 1,500 kg). Na unidade definida em `products.unit` (unidade de **venda**, nunca de compra).
3. **Escopo duplo:** toda query de negócio filtra `tenant_id` + `company_id` via traits `BelongsToTenant` e `BelongsToCompany`.
4. **Empresa no tenant:** `company_id` deve pertencer ao `tenant_id` ativo (garantido por FK + validação no middleware).
5. **Estoque:** `sgc_stock_movements` é append-only; saldo em `sgc_stock_items.quantity`. Escrita **somente** via `RegisterStockMovement`.
6. **Reconciliação:** `stock_items.quantity` deve bater com a soma dos movimentos da linha; verificar com `php artisan sgc:stock:reconcile`.
7. **Custo:** `average_cost` vive em `sgc_stock_items`, recalculado na entrada, intocado na saída; arredondar para centavos inteiros.
8. **Embalagem:** `package_factor` converte quantidade/custo na entrada; ledger e saldo sempre em unidade base (`products.unit`).
9. **Lotes com validade:** baixa por FEFO (`expires_at ASC`) quando `tracking = lot` e validade informada.
