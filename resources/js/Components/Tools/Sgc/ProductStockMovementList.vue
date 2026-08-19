<template>
    <Dialog
        overlay-id="sgc-product-stock-movements"
        title="Movimentações de estoque"
        max-width="720"
        no-body
        @close="handleClose"
        v-model="isOpen"
    >
        <v-skeleton-loader
            v-if="loading"
            type="list-item-two-line@4"
        ></v-skeleton-loader>

        <div
            v-else-if="movements.length === 0"
            class="d-flex align-center justify-center py-8"
        >
            <v-empty-state
                icon="mdi-history"
                title="Nenhuma movimentação"
                text="Ainda não há logs de estoque para este produto."
            ></v-empty-state>
        </div>

        <v-list
            v-else
            lines="two"
            style="max-height: 60dvh; overflow-y: auto;"
        >
            <v-list-item
                v-for="movement in movements"
                :key="movement.id"
                :prepend-icon="typeIcon(movement.type)"
                :title="movementTitle(movement)"
                :subtitle="movementSubtitle(movement)"
            >
                <template #append>
                    <v-chip
                        size="small"
                        variant="tonal"
                        :color="typeColor(movement.type)"
                    >
                        {{ typeLabel(movement.type) }}
                    </v-chip>
                </template>
            </v-list-item>
        </v-list>

        <template #actions>
            <v-btn
                prepend-icon="mdi-refresh"
                text="Atualizar"
                :disabled="loading"
                @click="loadMovements"
            ></v-btn>
        </template>
    </Dialog>
</template>

<script setup>
    import { ref } from "vue";

    import api from "@/api";

    import Dialog from "@/Components/Dialog.vue";

    import { alertError } from "@/Utils/Alert";
    import { formatDate, formatNumber } from "@/Utils/Common";

    const TYPE_LABELS = {
        entry: "Estoque inicial",
        sale: "Venda",
        cancellation: "Cancelamento",
        deletion: "Exclusão",
        adjustment: "Ajuste",
    };

    const TYPE_COLORS = {
        entry: "primary",
        sale: "success",
        cancellation: "warning",
        deletion: "error",
        adjustment: "info",
    };

    const TYPE_ICONS = {
        entry: "mdi-package-variant-closed-plus",
        sale: "mdi-cart-outline",
        cancellation: "mdi-cancel",
        deletion: "mdi-delete-outline",
        adjustment: "mdi-pencil-outline",
    };

    const isOpen = ref(false);
    const loading = ref(false);
    const movements = ref([]);
    const productId = ref(null);
    const companyId = ref(null);
    const companyProductId = ref(null);

    function typeLabel(type) {
        return TYPE_LABELS[type] || type;
    }

    function typeColor(type) {
        return TYPE_COLORS[type] || "default";
    }

    function typeIcon(type) {
        return TYPE_ICONS[type] || "mdi-swap-vertical";
    }

    function movementTitle(movement) {
        const before = formatNumber(movement.quantity_before, 3);
        const after = formatNumber(movement.quantity_after, 3);
        const delta = Number(movement.quantity_delta || 0);
        const sign = delta > 0 ? "+" : "";

        return `${before} → ${after} (${sign}${formatNumber(delta, 3)})`;
    }

    function movementSubtitle(movement) {
        const parts = [
            formatDate(movement.created_at, "dd/MM/yyyy HH:mm"),
            movement.user?.name,
            orderLabel(movement),
            movement.reason,
        ].filter(Boolean);

        return parts.join(" · ");
    }

    function orderLabel(movement) {
        if( !movement.reference_id ) {
            return null;
        }

        const type = String(movement.reference_type || "");

        if( !type.includes("SgcOrder") ) {
            return null;
        }

        return `Pedido #${movement.reference_id}`;
    }

    async function loadMovements() {
        if( !productId.value || !companyId.value ) {
            return;
        }

        loading.value = true;

        try {
            const url = route("api.tools.sgc.products.stock-movements.index", productId.value);
            const { data } = await api.get(url, {
                params: {
                    company_id: companyId.value,
                    company_product_id: companyProductId.value || undefined,
                    per_page: 100,
                },
            });

            movements.value = data.stock_movements || [];
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar movimentações");
        } finally {
            loading.value = false;
        }
    }

    function open(data = {}) {
        productId.value = data.productId ?? data.id ?? null;
        companyId.value = data.companyId ?? data.company_id ?? null;
        companyProductId.value = data.companyProductId ?? data.company_product_id ?? null;
        isOpen.value = true;

        loadMovements();
    }

    function handleClose() {
        movements.value = [];
        productId.value = null;
        companyId.value = null;
        companyProductId.value = null;
    }

    defineExpose({
        open,
    });
</script>
