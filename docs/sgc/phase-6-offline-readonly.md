# Fase 6 — Modo offline (somente leitura)

**Referência:** [schema.md](schema.md) · [conventions.md](conventions.md)

## Objetivo

Quando não houver conexão, o SGC entra em **modo somente leitura**: consultas a partir de cache local, sem criar, editar ou excluir dados. Ao voltar online, o modo normal é restaurado automaticamente.

## Pré-requisitos

- **Fases 0–5 concluídas.**
- Verificar: módulo SGC operacional online (clientes, produtos, estoque, pedidos).

## Escopo

### Comportamento esperado

| Estado | Leitura | Escrita |
|--------|---------|---------|
| Online | API (dados atuais) | Normal |
| Offline | Cache local (último snapshot) | **Bloqueada** |

**Offline = somente consulta.** Não há fila de sincronização, outbox nem merge de dados. Escritas ficam desabilitadas até a conexão voltar.

### Detecção de conectividade

- `window.navigator.onLine` + eventos `online` / `offline`.
- Interceptor axios: falha de rede (`ERR_NETWORK`, timeout, sem response) marca app como offline.
- Estado reativo global (composable ou provide/inject): `isOffline`, `lastSyncedAt`.

### Cache local (IndexedDB)

Usar **Dexie.js** (ou API IndexedDB nativa) — adicionar dependência no `package.json`.

Banco: `sgc_offline`, escopado por `tenant_id` + `company_id`.

Tabelas de cache (snapshot, não espelho completo do Postgres):

| Store | Conteúdo | Quando atualizar |
|-------|----------|------------------|
| `products` | Lista de produtos da empresa ativa | Ao entrar online em `/sgc/*` e após cada visita com rede |
| `customers` | Lista de clientes | Idem |
| `orders` | Pedidos (últimos N ou paginados) | Idem |
| `stock_items` | Linhas de saldo com quantity > 0 (opcional; para consulta offline) | Idem |
| `stock_movements` | Últimos movimentos (opcional, limit 100) | Idem |
| `meta` | `tenant_id`, `company_id`, `synced_at` | A cada sync |

**Sync (pull only):** ao detectar online + contexto SGC ativo, chamar endpoints GET existentes e gravar no IndexedDB. Não enviar escritas pendentes.

Endpoint dedicado opcional (simplifica sync):

| Método | Rota | Nome |
|--------|------|------|
| GET | `/api/sgc/offline/snapshot` | `api.sgc.offline.snapshot` |

Response agrega em uma request:

```json
{
  "synced_at": "2026-08-10T14:30:00Z",
  "products": [...],
  "customers": [...],
  "orders": [...],
  "stock_movements": [...],
  "stock_items": [...]
}
```

Se preferir mínimo de backend: reutilizar os `index` existentes no front e gravar no IndexedDB sem novo endpoint.

### Frontend — composable

- `resources/js/Utils/SGCOffline.js` (ou `Composables/useSGCOffline.js` se criar pasta)

Responsabilidades:

- `isOffline` (ref)
- `lastSyncedAt` (ref)
- `syncCache()` — pull quando online
- `getCachedProducts()`, `getCachedCustomers()`, etc.
- Registrar listeners `online`/`offline` no mount do layout SGC

### Frontend — UI

**Banner global** (fixo no topo do `/sgc/*`):

```vue
<v-alert
    v-if="isOffline"
    type="warning"
    density="compact"
    class="mb-4"
    text="Sem conexão. Modo somente leitura — dados podem estar desatualizados."
/>
```

**Desabilitar escritas quando offline:**

- Ocultar ou desabilitar `v-fab` de criar.
- Desabilitar botões Salvar, Confirmar, Excluir, Cancelar pedido.
- `VirtualRemoteTable`: manter listagem; se offline, ler do cache em vez de axios.
- Formulários: `:disabled="isOffline"` em todos os inputs e actions.

**Indicador de freshness:**

- Exibir `lastSyncedAt` formatado: "Atualizado em 10/08/2026 14:30" ou "Dados de cache".

### Interceptor axios

Em `resources/js/api.js` ou módulo SGC dedicado:

```js
api.interceptors.request.use((config) => {
    if (isOffline.value && ["post", "put", "patch", "delete"].includes(config.method?.toLowerCase())) {
        return Promise.reject({
            isOfflineBlock: true,
            message: "Sem conexão. Operação não permitida em modo somente leitura.",
        });
    }
    return config;
});
```

Tratar `isOfflineBlock` nos forms com `alertError`.

### PWA (opcional, recomendado)

- Service Worker (Vite PWA plugin ou manual) cacheando assets estáticos (JS, CSS, fontes).
- Permite **abrir a shell** do SGC offline; dados vêm do IndexedDB.
- **Não** cachear respostas de API no SW (usar IndexedDB com controle explícito).

### Páginas afetadas

Todas em `resources/js/Pages/SGC/`:

- `Index.vue`, `Companies.vue`, `Members.vue`, `Customers.vue`, `Products.vue`, `Stock.vue`, `Orders.vue`

Cada lista deve:

1. Se online → `VirtualRemoteTable` + API (comportamento atual).
2. Se offline → ler cache local; exibir banner; desabilitar ações de escrita.

## Fora do escopo

- Fila de operações offline (vendas/clientes pendentes de sync).
- Sync bidirecional IndexedDB ↔ Postgres.
- Edição ou criação offline com merge posterior.
- Notificações push quando voltar online.
- Cache de fotos de produtos (opcional futuro).

## Arquivos a criar/editar

| Ação | Caminho |
|------|---------|
| criar | `resources/js/Utils/SGCOffline.js` |
| criar | `resources/js/Components/SGC/OfflineBanner.vue` |
| criar | `app/Http/Controllers/SGC/OfflineSnapshotController.php` (opcional) |
| editar | `resources/js/api.js` (interceptor) |
| editar | `resources/js/Pages/SGC/*.vue` (banner + disable writes + fallback cache) |
| editar | `resources/js/Components/SGC/*FormFields.vue` (disabled quando offline) |
| editar | `routes/api.php` (rota snapshot, se usar endpoint dedicado) |
| editar | `package.json` (dexie, vite-plugin-pwa opcional) |

## Schema desta fase

Nenhuma tabela nova no Postgres. Cache é **apenas no navegador** (IndexedDB).

## Regras de negócio

1. Offline detectado → **nenhuma** escrita, em hipótese alguma, até voltar online.
2. Leituras offline usam **último snapshot**; exibir aviso de dados possivelmente desatualizados.
3. Ao voltar online: sync automático do cache + reabilitar UI de escrita.
4. Cache invalidado ao trocar tenant/empresa (limpar stores antigos).
5. Login inicial **requer** conexão (auth Sanctum); offline só após sessão já estabelecida.

## Contrato de API

### GET /api/sgc/offline/snapshot (opcional)

Auth: `auth` + `tenant`.

Response 200:

```json
{
  "synced_at": "2026-08-10T14:30:00Z",
  "products": [{ "id": 1, "description": "...", "stock_quantity": 5, ... }],
  "customers": [{ "id": 1, "name": "...", ... }],
  "orders": [{ "id": 1, "status": "confirmed", ... }],
  "stock_movements": [{ "id": 1, "type": "in", ... }],
  "stock_items": [{ "id": 1, "quantity": 50.000, "product_id": 3, "lot": { "code": "ROLO-001" }, ... }]
}
```

Limites sugeridos no controller: orders últimos 50, movements últimos 100, products/customers sem limite (empresa pequena).

## Frontend

- Composable `useSGCOffline()` exportando `isOffline`, `lastSyncedAt`, `syncCache`.
- Componente `OfflineBanner.vue` reutilizado em todas as pages SGC.
- Padrão de lista offline:

```js
const items = computed(async () => {
    if (isOffline.value) {
        return await getCachedProducts();
    }
    return null; // VirtualRemoteTable usa API
});
```

Ou: prop `:offline-items` no VirtualRemoteTable quando offline (avaliar extensão mínima do componente).

## Critérios de aceite

- [ ] Banner "modo somente leitura" visível quando offline.
- [ ] FABs e botões de criar/editar/excluir desabilitados offline.
- [ ] Interceptor axios bloqueia POST/PUT/PATCH/DELETE offline.
- [ ] Listas de produtos, clientes e pedidos funcionam offline com cache.
- [ ] `lastSyncedAt` exibido ao usuário.
- [ ] Ao voltar online, sync roda e UI de escrita reativa.
- [ ] Trocar empresa limpa cache da empresa anterior.
- [ ] Shell do SGC abre offline (PWA ou visita prévia) com dados em cache.

## Pegadinhas

- **`navigator.onLine` mente** — Wi-Fi conectado sem internet real; confiar também em falhas axios.
- **Sessão expirada offline** — usuário não consegue reautenticar; mensagem clara ao tentar ação que exige login.
- **VirtualRemoteTable** foi feito para API remota — offline pode exigir modo alternativo (lista estática) ou extensão leve do componente.
- **Tamanho do cache** — empresa com muitos produtos pode encher IndexedDB; considerar limite ou paginação no snapshot.
- **Busca por serial offline** — funciona se `stock_items`/serials estiverem no cache; senão, mensagem "disponível apenas online".
