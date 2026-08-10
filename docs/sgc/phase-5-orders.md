# Fase 5 — Vendas

**Referência:** [schema.md](schema.md) · [conventions.md](conventions.md)

## Objetivo

Pedidos com itens, confirmação em transação com baixa de estoque via `RegisterStockMovement`, snapshot de custo, seleção de linhas de saldo (serial/lote) quando exigido, cancelamento com estorno, e resolução da FK adiada da Fase 4.

## Pré-requisitos

- **Fase 4 concluída.**
- Verificar: `RegisterStockMovement` funcional; produtos com `tracking` none/lot/serial; clientes cadastrados.

## Escopo

### Migration

- `database/migrations/xxxx_create_sgc_orders_tables.php`
  - Tabelas: `sgc_orders`, `sgc_order_items` (ver [schema.md](schema.md)).
  - FKs reais para `sgc_tenants`, `sgc_companies`, `sgc_customers`, `sgc_products`.
  - `order_items.quantity` como `numeric(12,3)`.
  - **Adicionar FK pendente da Fase 4:**

```php
Schema::table("sgc_stock_movements", function (Blueprint $table) {
    $table->foreign("order_item_id")
        ->references("id")
        ->on("sgc_order_items")
        ->nullOnDelete();
});
```

  - (Recomendado) RLS em `sgc_orders` e `sgc_order_items`.

Rodar: `php artisan migrate`.

### Models

- `app/Models/SGC/Order.php` — `BelongsToTenant`, `BelongsToCompany`, `belongsTo Customer`, `hasMany OrderItem`.
- `app/Models/SGC/OrderItem.php` — `BelongsToTenant`, `belongsTo Order, Product`, `hasMany StockMovement`.

Constantes:

```php
// Order
public const STATUS_DRAFT = "draft";
public const STATUS_CONFIRMED = "confirmed";
public const STATUS_CANCELED = "canceled";

// Payment methods
public const PAYMENT_CASH = "cash";
public const PAYMENT_CREDIT = "credit";
public const PAYMENT_DEBIT = "debit";
public const PAYMENT_PIX = "pix";
```

### Action ConfirmOrder

- `app/Actions/SGC/ConfirmOrder.php`

Transação única. Input: `Order` em status `draft` com itens já persistidos. **Toda baixa de estoque delega a `RegisterStockMovement`** — não escrever em `stock_items` diretamente.

**Passos:**

1. Validar que todos os itens têm produto ativo.
2. Para cada item, conforme `product.tracking`:
   - **`none`:** validar saldo total (`SUM(stock_items.quantity) >= item.quantity`).
   - **`serial`:** validar `stock_item_ids` count === quantity (inteiros); todos com `quantity = 1`, pertencem ao produto.
   - **`lot`:** validar `item_allocations` somando `item.quantity`; linhas com saldo suficiente; FEFO se omitido.
3. Para cada item, chamar `RegisterStockMovement` com `type = out`, `reason = sale`, `order_item_id = item.id`:
   - **`none`:** uma saída na linha de saldo fungível (endereço default ou informado).
   - **`serial`:** uma saída por `stock_item_id`.
   - **`lot`:** uma ou mais saídas conforme `item_allocations`.
4. Calcular `item.unit_cost` = custo médio ponderado das linhas baixadas (`stock_items.average_cost` × quantidade alocada / quantidade total do item).
5. Calcular `total_amount` = sum(item.quantity * item.unit_price - item.discount_amount) - order.discount_amount.
6. Setar `order.status = confirmed`, `ordered_at = now()` (se não definido).

### Action CancelOrder

- `app/Actions/SGC/CancelOrder.php`

1. Validar `order.status === confirmed`.
2. Para cada item, buscar movimentos originais (`stock_movements` com `order_item_id = item.id`, `type = out`, `reason = sale`).
3. Para cada movimento original, chamar `RegisterStockMovement` com `type = in`, `reason = return`, `order_item_id = item.id`, `unit_cost = movimento original.unit_cost`, mesma `stock_item_id` e `quantity`.
4. Setar `order.status = canceled`.

Estorno recria saldo nas mesmas linhas de saldo (lote parcial volta ao lote original; serial volta à linha do aparelho).

### Backend

Controller: `app/Http/Controllers/SGC/OrderController.php`

| Método | Rota | Nome |
|--------|------|------|
| GET | `/api/sgc/orders` | `api.sgc.orders.index` |
| GET | `/api/sgc/orders/{order}` | `api.sgc.orders.show` |
| POST | `/api/sgc/orders` | `api.sgc.orders.store` |
| PUT | `/api/sgc/orders/{order}` | `api.sgc.orders.update` |
| POST | `/api/sgc/orders/{order}/confirm` | `api.sgc.orders.confirm` |
| POST | `/api/sgc/orders/{order}/cancel` | `api.sgc.orders.cancel` |
| DELETE | `/api/sgc/orders/{order}` | `api.sgc.orders.destroy` |

**Regras:**
- Store/update só permitido em status `draft`.
- Destroy só permitido em `draft`.
- Confirm e cancel via Actions.
- `customer_id` obrigatório.
- `tenant_id` e `company_id` vêm do contexto (traits).
- Quantidade do item na unidade de medida do produto (`product.unit`).

Form Requests: `StoreOrderRequest`, `UpdateOrderRequest`.

### Rotas web

```php
Route::inertia("/orders", "SGC/Orders")->name("sgc.orders");
```

### Frontend

- `resources/js/Pages/SGC/Orders.vue` — lista de pedidos.
- `resources/js/Components/SGC/OrderFormFields.vue` — Dialog fullscreen.

**OrderFormFields — estrutura:**

1. Select cliente (autocomplete, busca por nome/CPF).
2. Forma de pagamento + parcelas.
3. Tabela de itens (repetível):
   - Autocomplete produto (exibe `unit`).
   - Quantidade (decimal se `unit` ≠ `un`), preço unitário, desconto item.
   - Se `tracking = serial`: multiselect de linhas de saldo (`GET /api/sgc/stock/items?product_id=X&available=1` filtrando serials).
   - Se `tracking = lot`: alocação de lotes com quantidade por linha (ou deixar FEFO automático na confirmação).
4. Desconto total do pedido.
5. Total calculado (computed).
6. Botões: Salvar rascunho, Confirmar venda.

**Fluxo:**

- Salvar → POST/PUT (status draft).
- Confirmar → POST confirm (chama ConfirmOrder).
- Cancelar (pedido confirmado) → POST cancel com Dialog de confirmação.

**Seriais na venda:**

```vue
<v-autocomplete
    v-if="item.product?.tracking === 'serial'"
    label="Seriais"
    :items="availableStockItems"
    item-title="serial.code"
    item-value="id"
    v-model="item.stock_item_ids"
    multiple
    :rules="[v => v?.length === item.quantity || 'Selecione a quantidade correta']"
/>
```

Carregar `availableStockItems` ao selecionar produto (`GET /api/sgc/stock/items?product_id=X&available=1`).

## Fora do escopo

- Contas a receber / parcelas com vencimento.
- Emissão de NF-e.
- Devolução parcial (só cancelamento total no MVP).
- Desconto por percentual (MVP: valor fixo em centavos).
- Comissão de vendedor.

## Arquivos a criar/editar

| Ação | Caminho |
|------|---------|
| criar | `database/migrations/xxxx_create_sgc_orders_tables.php` |
| criar | `app/Models/SGC/Order.php` |
| criar | `app/Models/SGC/OrderItem.php` |
| criar | `app/Actions/SGC/ConfirmOrder.php` |
| criar | `app/Actions/SGC/CancelOrder.php` |
| criar | `app/Http/Controllers/SGC/OrderController.php` |
| criar | `app/Http/Requests/SGC/Order/StoreOrderRequest.php` |
| criar | `app/Http/Requests/SGC/Order/UpdateOrderRequest.php` |
| criar | `resources/js/Pages/SGC/Orders.vue` |
| criar | `resources/js/Components/SGC/OrderFormFields.vue` |
| editar | `routes/api.php` |
| editar | `routes/web.php` |
| editar | `app/Http/Controllers/SGC/StockItemController.php` (filtro available, eager load serial/lot) |

## Schema desta fase

- `sgc_orders`, `sgc_order_items` — [schema.md](schema.md)
- FK adiada: `sgc_stock_movements.order_item_id` — [schema.md](schema.md#fks-adiadas-fase-4--fase-5)

## Regras de negócio

1. Pedido confirmado é imutável (só cancelamento).
2. `unit_cost` snapshot na confirmação — nunca recalculado depois.
3. Cancelamento estorna estoque completamente via movimentos `in` com `reason = return`.
4. Baixa de serial zera `stock_item.quantity`; estorno restaura para 1.
5. Lote com consumo parcial: estorno incrementa a mesma linha de saldo.
6. Total em centavos; front exibe formatado.
7. Toda movimentação de estoque passa por `RegisterStockMovement`.

## Contrato de API

### POST /api/sgc/orders

Body:

```json
{
  "customer_id": 1,
  "payment_method": "credit",
  "installments": 3,
  "discount_amount": 0,
  "items": [
    {
      "product_id": 1,
      "quantity": 2.500,
      "unit_price": 1500,
      "discount_amount": 0
    },
    {
      "product_id": 2,
      "quantity": 1,
      "unit_price": 399900,
      "discount_amount": 5000,
      "stock_item_ids": [5]
    },
    {
      "product_id": 3,
      "quantity": 5.000,
      "unit_price": 800,
      "discount_amount": 0,
      "item_allocations": [{ "stock_item_id": 10, "quantity": 5.000 }]
    }
  ]
}
```

Response 201: `{ "order": { "id": 1, "status": "draft", "items": [...] } }`

### POST /api/sgc/orders/{id}/confirm

Sem body. Response 200:

```json
{
  "order": {
    "id": 1,
    "status": "confirmed",
    "total_amount": 415300,
    "items": [
      { "product_id": 1, "unit_cost": 1000, ... },
      { "product_id": 2, "unit_cost": 300000, "serials": [{ "code": "359999991234567" }] },
      { "product_id": 3, "unit_cost": 450, "lots": [{ "code": "LOTE-2026-001", "quantity": 5.000 }] }
    ]
  }
}
```

### POST /api/sgc/orders/{id}/cancel

Response 200: `{ "order": { "status": "canceled", ... } }`

## Frontend

- Lista: cliente, data, status (chip colorido), total formatado.
- Rascunho: editável; confirmado: somente visualizar + cancelar.
- Erro de estoque insuficiente na confirmação: `alertError` com mensagem clara.
- Quantidade com casas decimais quando produto vendido por kg/L/m.

## Critérios de aceite

- [ ] Migration cria `sgc_orders` e `sgc_order_items` com FKs reais e `quantity` decimal.
- [ ] FK adiada (`order_item_id`) adicionada nesta migration.
- [ ] Criar pedido rascunho com múltiplos itens (fungível, serial e lote).
- [ ] Confirmar pedido fungível baixa estoque via `RegisterStockMovement` e grava `unit_cost`.
- [ ] Confirmar pedido serial exige `stock_item_ids` e grava custo da linha.
- [ ] Confirmar pedido com lote permite consumo parcial.
- [ ] Recusa confirmação se estoque insuficiente.
- [ ] Cancelar pedido confirmado estorna estoque (incluindo lote parcial) via movimentos `return`.
- [ ] Busca por serial (Fase 4) mostra cliente e data após venda (via movimento com `order_item_id`).
- [ ] Página `/sgc/orders` operacional end-to-end.

## Pegadinhas

- **Confirmar duas vezes:** idempotência — se já `confirmed`, retornar 422.
- **Rascunho com stock_item_ids:** validar linhas só na confirmação; race condition aceita no MVP.
- **CancelOrder recalcula médio:** entrada de estorno fungível recalcula `average_cost` da linha via fórmula padrão.
- **Lote parcial no cancelamento:** incrementar `quantity` na mesma `stock_item`, não criar lote novo.
- **order_items.product_id:** produto inactive ainda pode estar em pedido draft; bloquear confirmação se inactive.
- **Total negativo:** validar que total >= 0 após descontos.
- **Ordem na migration:** criar `sgc_orders` e `sgc_order_items` **antes** de adicionar FK em `sgc_stock_movements`.
