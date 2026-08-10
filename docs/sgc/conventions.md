# Convenções — Módulo SGC

Padrões obrigatórios do projeto. Toda fase deve seguir estes exemplos.

---

## Backend

### Stack

- PHP ^8.3, Laravel ^13.8
- Sem Services, Repositories, Policies, Enums PHP, DTOs
- Lógica complexa em **Actions** invocáveis (`__invoke`)
- Utilitários estáticos em **Helpers**

### Controllers

Localização: `app/Http/Controllers/SGC/`

Controllers finos: query + `response()->json()` ou `Inertia::render()`. Sem `Route::resource()`.

**Paginação JSON** (padrão do projeto):

```php
$perPage = min($request->integer("per_page", 15), 100);

$paginator = Customer::query()
    ->select([...])
    ->orderByDesc("id")
    ->paginate($perPage);

return response()->json([
    "current_page" => $paginator->currentPage(),
    "last_page" => $paginator->lastPage(),
    "per_page" => $paginator->perPage(),
    "total" => $paginator->total(),
    "customers" => $paginator->items(),
], 200);
```

Chave no plural: `customers`, `products`, `orders`, etc.

Status HTTP: `200` (ok), `201` (created), `204` (no content).

### Form Requests

Localização: `app/Http/Requests/SGC/{Entidade}/`

```php
class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        // checar membership no tenant + role
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        // normalizar: strip dígitos de CPF, trim, etc.
    }

    public function rules(): array { ... }

    public function messages(): array
    {
        return [
            "name.required" => "O nome é obrigatório.",
            // mensagens em português
        ];
    }
}
```

### Models

- PK auto-increment (`$table->id()`)
- Status/enums como **constantes de classe**, não PHP Enums
- `$fillable` array (ou atributos PHP 8 `#[Fillable]`)
- `casts(): array` (método, não propriedade)
- Scopes: `scopeOwnedBy`, etc.
- Tabelas prefixadas: `protected $table = "sgc_customers";`
- Models em `app/Models/SGC/` (controle e negócio)

Models de negócio:

```php
class Customer extends Model
{
    use BelongsToTenant, BelongsToCompany;

    protected $table = "sgc_customers";

    protected $fillable = ["tenant_id", "company_id", "name", "document", ...];
}
```

Sem `protected $connection` — usa a conexão default (`pgsql`).

### Escopo de tenant/empresa

Contexto resolvido pelo middleware e lido pelos traits via `SGCContext`:

```php
// app/Helpers/SGCContext.php
class SGCContext
{
    private static ?int $tenantId = null;
    private static ?int $companyId = null;

    public static function set(int $tenantId, int $companyId): void { ... }
    public static function tenantId(): ?int { ... }
    public static function companyId(): ?int { ... }
    public static function forget(): void { ... }
}
```

Traits em `app/Models/Concerns/`:

- `BelongsToTenant` — global scope em `tenant_id` + preenche no `creating`.
- `BelongsToCompany` — global scope em `company_id` + preenche no `creating`.

Jobs e comandos artisan **sem sessão** devem chamar `SGCContext::set($tenantId, $companyId)` antes de queries de negócio.

### Actions

Localização: `app/Actions/SGC/`

```php
class RegisterStockMovement
{
    public function __invoke(array $data): StockMovement
    {
        return DB::transaction(function () use ($data) {
            // lógica
        });
    }
}
```

Injetar por type-hint no controller:

```php
public function store(StoreStockMovementRequest $request, RegisterStockMovement $action)
{
    $movement = $action($request->validated());
    return response()->json(["stock_movement" => $movement], 201);
}
```

### Rotas

**API** (`routes/api.php`):

```php
Route::middleware(["auth", "tenant"])
    ->prefix("sgc")
    ->group(function () {
        Route::get("/customers", [CustomerController::class, "index"])
            ->name("api.sgc.customers.index");
        // ...
    });
```

**Web** (`routes/web.php`):

```php
Route::middleware("auth")
    ->prefix("sgc")
    ->group(function () {
        Route::inertia("/", "SGC/Index")->name("sgc");
        Route::inertia("/customers", "SGC/Customers")->name("sgc.customers");
        // ...
    });
```

### Middleware

Registrar alias em `bootstrap/app.php`:

```php
$middleware->alias([
    "role" => EnsureUserHasRole::class,
    "tenant" => ResolveTenantContext::class,
]);
```

O middleware `ResolveTenantContext` valida membership, valida empresa no tenant, popula `SGCContext` e injeta atributos no request.

### Migrations

- Classes anônimas: `return new class extends Migration`
- FKs: `foreignId()->constrained("sgc_companies")->cascadeOnDelete()` / `nullOnDelete()`
- Todas em `database/migrations/` (autodiscovery)
- Rodar com: `php artisan migrate`
- Tabelas prefixadas: `$table->foreignId("tenant_id")->constrained("sgc_tenants")->cascadeOnDelete()`

### Dinheiro

**Sempre centavos (`integer`).** Evita erros de arredondamento e mantém consistência backend/front.

```php
// Backend: armazenar 1234 = R$ 12,34
"sale_price" => 1234,

// Frontend: exibir
(item.sale_price / 100).toLocaleString("pt-BR", { style: "currency", currency: "BRL" })
```

Nunca usar `float` para valores monetários.

### Quantidade

**Sempre `numeric(12,3)`** em `stock_items.quantity`, movimentos e itens de pedido. Permite venda fracionada (ex.: 1,500 kg, 3,000 m).

A unidade de **venda** fica em `products.unit` (`un`, `kg`, `g`, `L`, `ml`, `m`, `cm`). Exibir quantidade + unidade no front. Não usar `cx` — caixa é embalagem (`package_label`/`package_factor`).

```php
// Backend: armazenar 1.500 kg
"quantity" => 1.500,

// Custo médio da linha de saldo — arredondar para centavos inteiros
$stockItem->average_cost = (int) round(
    (($oldQty * $oldCost) + ($qty * $unitCost)) / $newQty
);
```

**Regra:** dinheiro = `integer` (centavos); quantidade = `numeric` (decimal). Não misturar.

### Embalagem

Compra e venda podem usar unidades diferentes. `package_label` + `package_factor` convertem na **entrada** de estoque; ledger e saldo sempre gravam em unidade base (`products.unit`).

```php
// Entrada de 2 rolos a R$ 30,00 cada (produto: unit=m, package_factor=50)
$quantity = $data["in_package"] ? $data["quantity"] * $product->package_factor : $data["quantity"];
// → 100 m

$unitCost = $data["in_package"]
    ? (int) round($data["unit_cost"] / $product->package_factor)
    : $data["unit_cost"];
// → 60 centavos/m
```

Arredondamento pode deixar resíduo de centavos — aceitável no MVP.

### Estoque

- **Saldo:** `sgc_stock_items.quantity` — única tabela que sofre UPDATE de quantidade.
- **Histórico:** `sgc_stock_movements` — append-only; nunca UPDATE/DELETE.
- **Escrita:** somente via `RegisterStockMovement`; controllers nunca escrevem em `stock_items` diretamente.
- **Saldo do produto:** `Product::withSum("stockItems as stock_quantity", "quantity")` — nunca coluna em `sgc_products`.
- **Reconciliação:** `php artisan sgc:stock:reconcile` compara saldo vs. ledger.

## Frontend

### Stack

- Vue 3 + Inertia 3 + Vuetify 4
- Axios para toda interação de dados (não usa `useForm` do Inertia)
- Ziggy para `route()` global
- Labels hardcoded em português (sem i18n)

### Layout

Tudo usa `Container.vue` como shell:

```vue
<Container title="Clientes" gapped>
    <!-- conteúdo -->
</Container>
```

Props: `centered` (tools/login), `gapped` (SGC), ou nenhuma.

### Lista (CRUD index)

Padrão de `resources/js/Pages/Admin/Blog/Posts.vue`:

```vue
<SearchField :fields="searchFields" @refresh="refresh" v-model="search" />

<VirtualRemoteTable
    ref="tableRef"
    items-key="customers"
    :href="route('api.sgc.customers.index')"
    :headers="headers"
    :filters="filters"
    :row-props="rowProps"
>
    <template #item.created_at="{ item }">
        {{ formatDate(item.created_at, "dd/MM/yyyy HH:mm") }}
    </template>
</VirtualRemoteTable>

<v-fab app icon="mdi-plus" color="primary" @click="formRef.open()" />
```

- `rowProps` com `onDblclick` para editar
- Refresh após mutação: `tableRef.value?.fetchItems(1)`

### Formulário (create/edit)

Dois estilos:

| Estilo | Quando | Exemplo |
|--------|--------|---------|
| `BottomSheet` | forms simples | AiProviderFormFields |
| `Dialog fullscreen` | forms complexos | PostFormFields |

Padrão do form:

```vue
<script setup>
const form = ref({});
const isDialogOpen = ref(false);

function open(data = {}) {
    form.value = { ...defaults, ...data };
    isDialogOpen.value = true;
    if (data.id) nextTick(() => fetchItem(data.id));
}

async function submit() {
    try {
        if (isEditing.value) {
            await api.put(route("api.sgc.customers.update", form.value.id), form.value);
        } else {
            await api.post(route("api.sgc.customers.store"), form.value);
        }
        toastSuccess("Ok", "Salvo com sucesso.");
        close();
        emit("form-closed");
    } catch (e) {
        alertError("Ooops", e.response?.data?.message ?? "Erro ao salvar.");
    }
}

defineExpose({ open });
</script>
```

### Autocomplete com manager inline

Padrão de `PostFormFields.vue`:

```vue
<v-autocomplete
    label="Categoria"
    item-title="name"
    item-value="id"
    :items="categories"
    v-model="form.category_id"
>
    <template #append-inner>
        <v-btn
            size="small"
            icon="mdi-playlist-edit"
            variant="tonal"
            color="primary"
            @click.prevent="categoryManagerRef.open()"
        />
    </template>
</v-autocomplete>
```

### Feedback

De `@/Utils/Alert.js`:

```js
import { toastSuccess, alertError } from "@/Utils/Alert";

toastSuccess("Ok", "Cliente salvo.");
alertError("Ooops", "Erro ao salvar.");
```

Confirmação de exclusão: `Dialog` dedicado com `overlay-id`, não SweetAlert confirm.

### API client

```js
import api from "@/api";
// axios com withCredentials, withXSRFToken
```

### Navegação

```js
import { goTo } from "@/Utils/Common";
goTo(route("sgc.customers"));
```

### Auth

```js
import { usePage } from "@inertiajs/vue3";
const user = computed(() => usePage().props.auth.user);
```

### Registro de ferramenta

Em `resources/js/Data/tools.js`:

```js
{
    key: "sgc",
    title: "SGC",
    subtitle: "Sistema de Gestão Comercial",
    icon: "mdi-store",
    href: route("sgc"),
    features: ["Clientes", "Produtos", "Estoque", "Vendas"],
}
```

---

## Modo offline (Fase 6)

Quando `isOffline === true`:

- **Leitura:** cache IndexedDB (último snapshot).
- **Escrita:** bloqueada no UI e no interceptor axios.
- Exibir banner: "Sem conexão. Modo somente leitura."
- Não implementar fila de sync nem outbox nesta fase.

---

## Nomenclatura

| Contexto | Idioma | Exemplo |
|----------|--------|---------|
| Sigla em prosa | **SGC** maiúsculo | "módulo SGC", "contexto SGC ativo" |
| Tabelas/colunas | inglês, prefixo minúsculo | `sgc_customers`, `stock_items` |
| Classes/namespaces PHP | PascalCase, sigla maiúscula | `SGCContext`, `app/Models/SGC/Customer` |
| Variáveis/funções JS | camelCase, sigla maiúscula | `SGCTenantId`, `useSGCOffline()` |
| Rotas/URLs | minúsculo | `/sgc`, `api.sgc.customers.index` |
| Labels UI | português | "Nome completo", "Quantidade" |
| Mensagens de validação | português | "O nome é obrigatório." |
| Constantes de status | inglês | `STATUS_CONFIRMED = "confirmed"` |
| Quantidade | `numeric(12,3)` + `unit` | `1.500` kg, `100` m, `2` un |
