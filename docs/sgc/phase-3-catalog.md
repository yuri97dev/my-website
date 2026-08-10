# Fase 3 — Catálogo

**Referência:** [schema.md](schema.md) · [conventions.md](conventions.md)

## Objetivo

CRUD de categorias, marcas e produtos, com autocompletes inline (manager) e upload de foto.

## Pré-requisitos

- **Fase 2 concluída.**
- Verificar: traits `BelongsToTenant` e `BelongsToCompany` funcionais; CRUD de clientes operacional.

## Escopo

### Migration

- `database/migrations/xxxx_create_sgc_catalog_tables.php`
  - Tabelas: `sgc_product_categories`, `sgc_product_brands`, `sgc_products` (ver [schema.md](schema.md)).
  - FKs reais para `sgc_tenants`, `sgc_companies`, categorias e marcas.
  - (Recomendado) RLS nas três tabelas.

Rodar: `php artisan migrate`.

### Models

- `app/Models/SGC/ProductCategory.php` — `BelongsToTenant`, `BelongsToCompany`.
- `app/Models/SGC/ProductBrand.php` — `BelongsToTenant`, `BelongsToCompany`.
- `app/Models/SGC/Product.php` — `BelongsToTenant`, `BelongsToCompany`, `belongsTo category, brand`, `hasMany StockItem`.

Constantes de `tracking` e `unit` conforme [schema.md](schema.md#sgc_products).

### Backend — Categorias e Marcas

Controllers:

- `app/Http/Controllers/SGC/ProductCategoryController.php`
- `app/Http/Controllers/SGC/ProductBrandController.php`

Endpoints (padrão CRUD, auth + tenant):

| Recurso | Index | Store | Update | Destroy |
|---------|-------|-------|--------|---------|
| categories | `api.sgc.categories.index` | `.store` | `.update` | `.destroy` |
| brands | `api.sgc.brands.index` | `.store` | `.update` | `.destroy` |

Index retorna lista simples (sem paginação) para alimentar autocompletes:

```json
{ "categories": [{ "id": 1, "name": "Eletrônicos" }] }
```

Limite: 500 itens (empresa pequena).

### Backend — Produtos

Controller: `app/Http/Controllers/SGC/ProductController.php`

CRUD completo + upload de foto.

| Método | Rota | Nome |
|--------|------|------|
| GET | `/api/sgc/products` | `api.sgc.products.index` |
| GET | `/api/sgc/products/{product}` | `api.sgc.products.show` |
| POST | `/api/sgc/products` | `api.sgc.products.store` |
| PUT | `/api/sgc/products/{product}` | `api.sgc.products.update` |
| DELETE | `/api/sgc/products/{product}` | `api.sgc.products.destroy` |
| POST | `/api/sgc/products/{product}/photo` | `api.sgc.products.photo.store` |

**Upload de foto:**
- Armazenar em `storage/app/private/sgc/products/{company_id}/{product_id}.{ext}`.
- Salvar `photo_path` relativo no model.
- Max size: constante (ex.: 2 MB).
- Formatos: jpg, png, webp.

**Regras:**
- `sale_price` em centavos (por unidade de medida de **venda**).
- `tracking`: `none` (padrão), `lot` ou `serial` — define como o estoque será rastreado na Fase 4.
- `unit`: unidade de **venda** (`un`, `kg`, `g`, `L`, `ml`, `m`, `cm`). Default `un`. Não incluir `cx` — caixa é embalagem (`package_label`/`package_factor`).
- `package_label` e `package_factor`: conversão opcional na entrada de estoque (Fase 4). Default `package_factor = 1`.
- Saldo **não** fica em `sgc_products`; na listagem usar `withSum("stockItems as stock_quantity", "quantity")`.
- SKU unique por `(company_id, sku)` quando preenchido.
- Respeitar `TenantLimit::records()`.
- **Não permitir alterar `tracking`** se o produto tiver saldo > 0 (soma de `stock_items.quantity`).

### Rotas web

```php
Route::inertia("/products", "SGC/Products")->name("sgc.products");
```

### Frontend

**Pages:**

- `resources/js/Pages/SGC/Products.vue`

**Components:**

- `resources/js/Components/SGC/ProductFormFields.vue` — Dialog fullscreen (form complexo).
- `resources/js/Components/SGC/CategoryManager.vue` — Dialog para CRUD inline de categorias.
- `resources/js/Components/SGC/BrandManager.vue` — Dialog para CRUD inline de marcas.

**ProductFormFields — autocompletes:**

Replicar padrão de `PostFormFields.vue`:

```vue
<v-autocomplete
    label="Categoria"
    item-title="name"
    item-value="id"
    :items="categories"
    v-model="form.category_id"
>
    <template #append-inner>
        <v-btn icon="mdi-playlist-edit" @click.prevent="categoryManagerRef.open()" />
    </template>
</v-autocomplete>
```

Mesmo para marca.

**Campos do produto:**

- description, sku, category_id, brand_id, sale_price (input em reais, converter para centavos no submit).
- tracking (select: Sem rastreio / Por lote / Por unidade).
- unit (select: un, kg, g, L, ml, m, cm).
- package_label (texto, opcional), package_factor (numérico, default 1) — exibir hint quando preenchido: "1 {package_label} = {package_factor} {unit}".
- active (switch), photo (file input ou cropper).

**Conversão de preço no front:**

```js
// exibir: product.sale_price / 100
// enviar: Math.round(parseFloat(form.sale_price_display) * 100)
```

## Fora do escopo

- Movimentação de estoque (Fase 4).
- Lotes, seriais, endereços (Fase 4).
- Vendas (Fase 5).
- Variações de produto (tamanho, cor) — usar um produto por variação com SKU próprio.
- Código de barras EAN.

## Arquivos a criar/editar

| Ação | Caminho |
|------|---------|
| criar | `database/migrations/xxxx_create_sgc_catalog_tables.php` |
| criar | `app/Models/SGC/ProductCategory.php` |
| criar | `app/Models/SGC/ProductBrand.php` |
| criar | `app/Models/SGC/Product.php` |
| criar | `app/Http/Controllers/SGC/ProductCategoryController.php` |
| criar | `app/Http/Controllers/SGC/ProductBrandController.php` |
| criar | `app/Http/Controllers/SGC/ProductController.php` |
| criar | `app/Http/Requests/SGC/Product/*.php` |
| criar | `app/Http/Requests/SGC/Category/*.php` |
| criar | `app/Http/Requests/SGC/Brand/*.php` |
| criar | `resources/js/Pages/SGC/Products.vue` |
| criar | `resources/js/Components/SGC/ProductFormFields.vue` |
| criar | `resources/js/Components/SGC/CategoryManager.vue` |
| criar | `resources/js/Components/SGC/BrandManager.vue` |
| editar | `routes/api.php` |
| editar | `routes/web.php` |

## Schema desta fase

- `sgc_product_categories`, `sgc_product_brands`, `sgc_products` — [schema.md](schema.md)

## Regras de negócio

1. Categoria/marca unique por `(company_id, name)`.
2. Produto inactive (`active = false`) não aparece em autocompletes de venda (Fase 5), mas aparece na lista admin.
3. Excluir categoria/marca: `nullOnDelete` em products (category_id/brand_id ficam null).
4. Excluir produto: permitir só se saldo = 0 (`SUM(stock_items.quantity) = 0`).
5. `tracking` imutável com saldo > 0.

## Contrato de API

### POST /api/sgc/products

Body:

```json
{
  "description": "Fita isolante 19mm",
  "sku": "FITA-19",
  "category_id": 1,
  "brand_id": 2,
  "sale_price": 350,
  "tracking": "lot",
  "unit": "m",
  "package_label": "Rolo",
  "package_factor": 50,
  "active": true
}
```

Response 201: `{ "product": { ... } }`

### GET /api/sgc/products (index)

Incluir saldo agregado:

```json
{
  "products": [
    {
      "id": 1,
      "description": "Fita isolante 19mm",
      "unit": "m",
      "stock_quantity": 100.000,
      "tracking": "lot"
    }
  ]
}
```

### POST /api/sgc/products/{id}/photo

Content-Type: `multipart/form-data`, field: `photo`.

Response 200: `{ "product": { "photo_path": "sgc/products/1/5.jpg" } }`

## Frontend

- Lista produtos com colunas: description, sku, category, brand, sale_price (formatado), stock_quantity + unit, tracking, active.
- Duplo clique abre edição.
- CategoryManager/BrandManager: lista + form inline, `defineExpose({ open })`, emite `@updated` para recarregar autocomplete.

## Critérios de aceite

- [ ] Migration cria categorias, marcas e produtos com FKs reais e `tenant_id`.
- [ ] CRUD de categorias e marcas via API.
- [ ] CRUD de produtos com preço em centavos.
- [ ] Select `tracking` (none/lot/serial) e `unit` (inclui `m`, sem `cx`) persistidos.
- [ ] Campos `package_label` e `package_factor` persistidos.
- [ ] Index de produtos retorna `stock_quantity` via `withSum`.
- [ ] Autocomplete de categoria/marca com botão de manager inline.
- [ ] Upload de foto funciona e exibe preview.
- [ ] Não permite excluir produto com estoque > 0.
- [ ] Não permite alterar `tracking` com estoque > 0.
- [ ] Página `/sgc/products` operacional.

## Pegadinhas

- **Managers inline:** após criar categoria no manager, atualizar lista do autocomplete e selecionar o novo item.
- **Foto no update:** decidir se substitui ou mantém; deletar arquivo antigo ao substituir.
- **sale_price no form:** sempre converter reais ↔ centavos; nunca enviar float para API.
- **Preço por unidade:** `sale_price` é por kg/L/m/un conforme `unit` — exibir a unidade no label do campo.
- **Embalagem no cadastro:** `package_factor` só afeta entrada de estoque (Fase 4); no catálogo é metadado do produto.
