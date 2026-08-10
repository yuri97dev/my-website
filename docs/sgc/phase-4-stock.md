# Fase 4 — Estoque

**Referência:** [schema.md](schema.md) · [conventions.md](conventions.md)

## Objetivo

Saldo mutável em `sgc_stock_items`, kardex append-only em `sgc_stock_movements`, identidade em lotes e seriais, endereçamento opcional, conversão de embalagem na entrada, e busca por identificador.

## Pré-requisitos

- **Fase 3 concluída.**
- Verificar: produtos com `tracking`, `unit`, `package_label` e `package_factor` definidos.

## Escopo

### Migration

- `database/migrations/xxxx_create_sgc_stock_tables.php`
  - Tabelas: `sgc_stock_locations`, `sgc_product_lots`, `sgc_product_serials`, `sgc_stock_items`, `sgc_stock_movements` (ver [schema.md](schema.md)).
  - FKs reais para `sgc_tenants`, `sgc_companies`, `sgc_products`, e entre as tabelas de estoque.
  - **`order_item_id` em `sgc_stock_movements` como `bigint` nullable sem constraint** — FK adicionada na Fase 5 (ver [schema.md](schema.md#fks-adiadas-fase-4--fase-5)).
  - CHECKs e `UNIQUE NULLS NOT DISTINCT` conforme schema.
  - (Recomendado) RLS em todas as tabelas.

Rodar: `php artisan migrate`.

### Models

- `app/Models/SGC/StockLocation.php` — `BelongsToTenant`, `BelongsToCompany`, `hasMany StockItem`.
- `app/Models/SGC/ProductLot.php` — `BelongsToTenant`, `BelongsToCompany`, `belongsTo Product`, `hasMany StockItem`.
- `app/Models/SGC/ProductSerial.php` — `BelongsToTenant`, `BelongsToCompany`, `belongsTo Product`, `hasOne StockItem`.
- `app/Models/SGC/StockItem.php` — `BelongsToTenant`, `BelongsToCompany`, `belongsTo Product, ProductLot, ProductSerial, StockLocation`, `hasMany StockMovement`.
- `app/Models/SGC/StockMovement.php` — `BelongsToTenant`, `BelongsToCompany`, `belongsTo StockItem, Product`.

Constantes conforme [schema.md](schema.md#sgc_stock_movements).

### Action RegisterStockMovement

- `app/Actions/SGC/RegisterStockMovement.php`

**Único ponto de escrita** em `sgc_stock_items` e `sgc_stock_movements`. Executa em `DB::transaction()`. Nenhum controller escreve saldo diretamente.

**Input:**

```php
[
    "product_id" => 1,
    "type" => "in",              // in | out
    "reason" => "purchase",      // purchase | sale | return | defect | loss | expiry | inventory | transfer
    "quantity" => 10.000,        // na unidade do produto (ou embalagem se in_package)
    "unit_cost" => 300000,       // centavos por unidade base; obrigatório em "in"
    "in_package" => false,       // true: quantity/unit_cost referem-se à embalagem
    "location_id" => null,       // opcional
    "lots" => [                  // obrigatório se tracking = lot
        [
            "code" => "ROLO-001",
            "quantity" => 50.000,
            "expires_at" => null,
        ],
    ],
    "serials" => [               // obrigatório se tracking = serial
        [
            "code" => "359999991234567",
            "attributes" => ["imei2" => "359999992345678"],
        ],
    ],
    "stock_item_ids" => [1, 2],  // saída serial: ids de stock_items
    "item_allocations" => [      // saída lote: [{ "stock_item_id": 1, "quantity": 2.5 }, ...]
        ["stock_item_id" => 10, "quantity" => 5.000],
    ],
    "order_item_id" => null,     // preenchido na Fase 5 (venda/estorno)
    "notes" => "",
    "occurred_at" => "2026-08-08 14:00:00",
]
```

**Passos comuns (toda operação):**

1. Carregar produto; validar `tracking` vs. payload (`lots`/`serials`/`item_allocations`).
2. Se `in_package`: converter quantidade e custo para unidade base (ver [conventions.md](conventions.md#embalagem)).
3. Resolver/criar `sgc_stock_items` alvo(s).
4. `SELECT ... FOR UPDATE` na(s) linha(s) de saldo.
5. Validar saldo na saída; FEFO automático se `item_allocations` omitido e `tracking = lot`.
6. Recalcular `average_cost` na entrada (fórmula em conventions).
7. `UPDATE stock_items.quantity` (+ `average_cost` se entrada).
8. `INSERT stock_movements` com `stock_item_id`, `reason`, `order_item_id`.

**Lógica — `tracking = none` (fungível):**

- Uma linha de saldo por `(product_id, location_id)` com `lot_id` e `serial_id` nulos.
- Entrada: incrementa `quantity`; recalcula `average_cost`.
- Saída: decrementa `quantity`; recusa se insuficiente; `average_cost` não muda.
- Inventário: `reason = inventory` — comparar saldo físico contado vs. sistema; gerar `in` ou `out` pela diferença.

**Lógica — `tracking = serial`:**

Entrada (`in`):
- Exige `serials` com `count === quantity` (inteiros).
- Para cada serial: criar `product_serial`, criar `stock_item` com `quantity = 1`, `average_cost = unit_cost`.
- Um movimento `in` por serial (ou um movimento agregado por serial — preferir **um movimento por serial** para rastreabilidade).

Saída (`out`):
- Exige `stock_item_ids` com exatamente `$quantity` ids.
- Valida `quantity = 1` em cada linha, pertencem ao produto.
- Decrementa para 0; `reason = defect` (manual) ou `sale` (Fase 5).

**Lógica — `tracking = lot`:**

Entrada (`in`):
- Exige `lots` array; cada item: `code`, `quantity`, `expires_at` opcional.
- Para cada lote: criar `product_lot` se novo; criar/atualizar `stock_item` daquele lote.
- Incrementa `quantity` e recalcula `average_cost` da linha.

Saída (`out`):
- Exige `item_allocations` somando `$quantity`, ou FEFO automático (lotes com `quantity > 0`, ordenados por `expires_at ASC NULLS LAST`).
- Decrementa `quantity` de cada linha; recusa se algum lote ficaria negativo.

**Transferência entre endereços (`reason = transfer`):**

- Dois movimentos na mesma transação: `out` na linha de origem + `in` na linha de destino (mesmo `unit_cost` da origem).
- Se não existir linha no destino, criar com saldo 0 antes do `in`.

**Invariante pós-operação:**
```
stock_item.quantity === SUM(in) - SUM(out) dos movimentos da linha
```

### Comando sgc:stock:reconcile

- `app/Console/Commands/SGC/ReconcileStockCommand.php`

Detecta divergências entre `stock_items.quantity` e o ledger. Flag `--fix` recalcula saldo a partir dos movimentos (uso admin).

Incluir teste de feature que valida invariante após cada cenário de movimentação.

### Backend

Controllers:

- `app/Http/Controllers/SGC/StockLocationController.php` — CRUD simples de endereços.
- `app/Http/Controllers/SGC/StockMovementController.php` — index + store (via Action).
- `app/Http/Controllers/SGC/StockItemController.php` — index por produto/lote/serial.
- `app/Http/Controllers/SGC/ProductSerialController.php` — index + search.

| Método | Rota | Nome |
|--------|------|------|
| GET | `/api/sgc/stock/locations` | `api.sgc.stock.locations.index` |
| POST | `/api/sgc/stock/locations` | `api.sgc.stock.locations.store` |
| PUT | `/api/sgc/stock/locations/{location}` | `api.sgc.stock.locations.update` |
| GET | `/api/sgc/stock/movements` | `api.sgc.stock.movements.index` |
| POST | `/api/sgc/stock/movements` | `api.sgc.stock.movements.store` |
| GET | `/api/sgc/stock/items` | `api.sgc.stock.items.index` |
| GET | `/api/sgc/stock/serials/search` | `api.sgc.stock.serials.search` |

**GET /api/sgc/stock/serials/search?code=359999991234567&product_id=2`**

Query `product_id` opcional. Retorna serial com produto, saldo (`stock_item.quantity`), custo (`stock_item.average_cost`), localização, cliente (se vendido — Fase 5 via movimento com `order_item_id`).

**GET /api/sgc/stock/items?product_id=1&available=1`**

Lista linhas de saldo; `available=1` filtra `quantity > 0`. Usado na venda (Fase 5) para selecionar seriais/lotes.

Form Requests: `StoreStockMovementRequest`, `StoreStockLocationRequest`.

### Rotas web

```php
Route::inertia("/stock", "SGC/Stock")->name("sgc.stock");
Route::inertia("/stock/locations", "SGC/StockLocations")->name("sgc.stock.locations");
```

### Frontend

- `resources/js/Pages/SGC/Stock.vue`
  - Aba/lista de movimentos (VirtualRemoteTable).
  - Form de entrada/saída (BottomSheet ou Dialog).
  - Busca por serial (IMEI).
  - Card de saldo por produto (soma de items).

- `resources/js/Pages/SGC/StockLocations.vue` — CRUD de endereços (opcional; pode ficar em aba dentro de Stock no MVP).

- `resources/js/Components/SGC/StockMovementFormFields.vue`
  - Select produto (autocomplete) — exibe `tracking`, `unit`, `package_label`.
  - Select tipo (entrada/saída) + motivo (`reason`).
  - Checkbox "Informar em {package_label}" quando produto tem embalagem.
  - Quantidade e custo unitário (entrada).
  - Select endereço (opcional).
  - Se `tracking = serial`: textarea de códigos (um por linha) + attributes opcionais.
  - Se `tracking = lot`: seção repetível com code, quantidade, validade (opcional).

**Entrada serializada — UI:**

```vue
<v-textarea
    v-if="selectedProduct?.tracking === 'serial'"
    label="Códigos (um por linha)"
    v-model="serialsText"
    :hint="`Informe exatamente ${form.quantity} código(s)`"
/>
```

**Entrada por lote — UI:**

```vue
<div v-if="selectedProduct?.tracking === 'lot'" v-for="(lot, i) in form.lots" :key="i">
    <v-text-field label="Código do lote/rolo" v-model="lot.code" />
    <v-text-field label="Quantidade" v-model="lot.quantity" type="number" step="0.001" />
    <v-text-field label="Validade (opcional)" v-model="lot.expires_at" type="date" />
</div>
```

**Entrada com embalagem — UI:**

```vue
<v-checkbox
    v-if="selectedProduct?.package_label"
    :label="`Informar em ${selectedProduct.package_label}`"
    v-model="form.in_package"
/>
```

## Fora do escopo

- Vendas e baixa automática por pedido (Fase 5).
- Transferência entre empresas.
- Reserva/bloqueio (`quantity_reserved` existe no schema, sem UI).
- Trigger automático no Postgres (documentado como opção futura).
- FK de `order_item_id` (Fase 5).

## Arquivos a criar/editar

| Ação | Caminho |
|------|---------|
| criar | `database/migrations/xxxx_create_sgc_stock_tables.php` |
| criar | `app/Models/SGC/StockLocation.php` |
| criar | `app/Models/SGC/ProductLot.php` |
| criar | `app/Models/SGC/ProductSerial.php` |
| criar | `app/Models/SGC/StockItem.php` |
| criar | `app/Models/SGC/StockMovement.php` |
| criar | `app/Actions/SGC/RegisterStockMovement.php` |
| criar | `app/Console/Commands/SGC/ReconcileStockCommand.php` |
| criar | `app/Http/Controllers/SGC/StockLocationController.php` |
| criar | `app/Http/Controllers/SGC/StockMovementController.php` |
| criar | `app/Http/Controllers/SGC/StockItemController.php` |
| criar | `app/Http/Controllers/SGC/ProductSerialController.php` |
| criar | `app/Http/Requests/SGC/Stock/StoreStockMovementRequest.php` |
| criar | `app/Http/Requests/SGC/Stock/StoreStockLocationRequest.php` |
| criar | `resources/js/Pages/SGC/Stock.vue` |
| criar | `resources/js/Pages/SGC/StockLocations.vue` (ou aba em Stock.vue) |
| criar | `resources/js/Components/SGC/StockMovementFormFields.vue` |
| editar | `routes/api.php` |
| editar | `routes/web.php` |

## Schema desta fase

- `sgc_stock_locations`, `sgc_product_lots`, `sgc_product_serials`, `sgc_stock_items`, `sgc_stock_movements` — [schema.md](schema.md)
- Coluna `order_item_id` **sem FK** nesta fase.

## Regras de negócio

1. `sgc_stock_movements` é append-only — nunca UPDATE/DELETE.
2. Saldo mutável só em `sgc_stock_items`, sempre via `RegisterStockMovement`.
3. `average_cost` recalculado na entrada da linha de saldo; intocado na saída.
4. Código de lote/serial unique por produto.
5. FEFO quando `tracking = lot` e lotes têm `expires_at`; senão FIFO por `stock_item.id`.
6. Inventário via `reason = inventory` (entrada ou saída pela diferença); não existe tipo `adjustment`.
7. Embalagem convertida na entrada; ledger e saldo sempre em unidade base.

## Contrato de API

### POST /api/sgc/stock/movements

Body (fungível):

```json
{
  "product_id": 1,
  "type": "in",
  "reason": "purchase",
  "quantity": 10.000,
  "unit_cost": 1000,
  "notes": "Compra fornecedor X",
  "occurred_at": "2026-08-08T14:00:00"
}
```

Body (serial):

```json
{
  "product_id": 2,
  "type": "in",
  "reason": "purchase",
  "quantity": 2,
  "unit_cost": 300000,
  "serials": [
    { "code": "359999991234567", "attributes": { "imei2": "359999992345678" } },
    { "code": "359999993456789" }
  ],
  "occurred_at": "2026-08-08T14:00:00"
}
```

Body (lote / rolo de fita):

```json
{
  "product_id": 3,
  "type": "in",
  "reason": "purchase",
  "quantity": 100.000,
  "in_package": true,
  "unit_cost": 3000,
  "lots": [
    { "code": "ROLO-001", "quantity": 50.000 },
    { "code": "ROLO-002", "quantity": 50.000 }
  ],
  "occurred_at": "2026-08-08T14:00:00"
}
```

Response 201:

```json
{
  "stock_movements": [{ "id": 1, "type": "in", "quantity": 50.000, "stock_item_id": 10, ... }],
  "product": { "id": 3, "stock_quantity": 100.000 }
}
```

### GET /api/sgc/stock/serials/search?code=359999991234567

Response 200:

```json
{
  "serials": [
    {
      "id": 1,
      "code": "359999991234567",
      "product": { "id": 2, "description": "Samsung Galaxy S24" },
      "stock_item": {
        "id": 5,
        "quantity": 1,
        "average_cost": 300000,
        "location": { "code": "A1", "name": "Prateleira A1" }
      },
      "attributes": { "imei2": "359999992345678" }
    }
  ]
}
```

## Frontend

- Lista movimentos: produto, tipo, motivo, quantidade + unit, custo, endereço, data.
- Saldo atual por produto (card ou coluna derivada de items).
- Busca por serial: input + botão; exibe card com produto, saldo, localização, histórico de movimentos.

## Critérios de aceite

- [ ] Migration cria as 5 tabelas de estoque com FKs e CHECKs (exceto `order_item_id`).
- [ ] Entrada fungível recalcula `average_cost` da linha de saldo.
- [ ] Entrada serial cria serial + stock_item + movimento por unidade.
- [ ] Entrada por lote cria lote + stock_item com quantidade e validade opcional.
- [ ] Entrada com `in_package` converte quantidade/custo para unidade base.
- [ ] Saída fungível decrementa saldo; recusa se insuficiente.
- [ ] Saída por lote com consumo parcial funciona; FEFO automático quando aplicável.
- [ ] Código duplicado no mesmo produto retorna erro de validação.
- [ ] Busca por serial retorna produto, saldo e custo.
- [ ] `sgc:stock:reconcile` detecta divergência em teste artificial.
- [ ] Endereços opcionais funcionam; transferência entre endereços.
- [ ] Página `/sgc/stock` operacional.

## Pegadinhas

- **Custo médio com decimal:** usar `round()` no resultado final, não `intdiv`.
- **Embalagem:** rolo de R$ 99,90 com 50 m → 199,8 centavos/m arredonda para 200; documentar resíduo.
- **Saída serial manual:** `reason = defect`; Fase 5 usa `reason = sale`.
- **Lote parcial:** linha mantém `quantity > 0` após venda parcial.
- **FEFO sem validade:** lotes sem `expires_at` vão para o final (`NULLS LAST`).
- **Concorrência:** sempre `FOR UPDATE` na linha de saldo antes de validar/decrementar.
- **FK adiada:** não tentar adicionar constraint para `sgc_order_items` nesta fase.
- **Reconciliação:** rodar após testes de integração de estoque.
