<template>
    <Dialog
        overlay-id="sgc-product-units"
        :title="dialogTitle"
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
            v-else-if="units.length === 0"
            class="d-flex align-center justify-center py-8"
        >
            <v-empty-state
                icon="mdi-package-variant"
                title="Nenhuma unidade"
                text="Não há unidades cadastradas para este produto."
            ></v-empty-state>
        </div>

        <v-list
            v-else
            lines="two"
            style="height: 50dvh; overflow-y: auto;"
        >
            <v-list-item
                v-for="unit in units"
                :key="unit.id"
                prepend-icon="mdi-barcode"
                :title="unitTitle(unit)"
                :subtitle="unitSubtitle(unit)"
                style="cursor: pointer;"
                @click="selectUnit(unit)"
            ></v-list-item>
        </v-list>

        <template #actions>
            <v-btn
                prepend-icon="mdi-refresh"
                text="Atualizar"
                :disabled="loading"
                @click="loadUnits"
            ></v-btn>
        </template>
    </Dialog>
</template>

<script setup>
    import { ref, computed } from "vue";

    import api from "@/api";

    import Dialog from "@/Components/Dialog.vue";

    import { alertError } from "@/Utils/Alert";
    import { formatNumber } from "@/Utils/Common";
    import { formatMoney } from "@/Utils/Money";

    const emit = defineEmits([
        "select",
    ]);

    const isOpen = ref(false);
    const loading = ref(false);
    const units = ref([]);
    const productId = ref(null);
    const companyId = ref(null);
    const productName = ref("");

    const dialogTitle = computed(() => {
        if( !productName.value ) {
            return "Unidades do produto";
        }

        return `Unidades — ${productName.value}`;
    });

    function unitTitle(unit) {
        if( unit.serial_number ) {
            return unit.serial_number;
        }

        return `Unidade #${unit.id}`;
    }

    function unitSubtitle(unit) {
        const parts = [
            `Estoque: ${formatNumber(unit.stock_quantity, 3)}`,
        ];

        if( unit.unit_of_measure?.abbreviation ) {
            parts.push(unit.unit_of_measure.abbreviation);
        }

        if( unit.sale_price != null ) {
            parts.push(`R$ ${formatMoney(unit.sale_price)}`);
        }

        return parts.join(" · ");
    }

    function selectUnit(unit) {
        emit("select", {
            id: productId.value,
            company_id: unit.company_id,
            company_product_id: unit.id,
            serial_number: unit.serial_number,
            sale_price: unit.sale_price,
            cost_price: unit.cost_price,
            stock_quantity: unit.stock_quantity,
            unit_of_measure_id: unit.unit_of_measure_id,
            unit_of_measure: unit.unit_of_measure?.abbreviation ?? null,
            pivot: {
                id: unit.id,
                sale_price: unit.sale_price,
                cost_price: unit.cost_price,
                stock_quantity: unit.stock_quantity,
                serial_number: unit.serial_number,
                unit_of_measure_id: unit.unit_of_measure_id,
            },
        });
    }

    async function loadUnits() {
        if( !productId.value ) {
            return;
        }

        loading.value = true;

        try {
            const url = route("api.tools.sgc.products.units.index", productId.value);

            const { data } = await api.get(url, {
                params: {
                    company_id: companyId.value || undefined,
                },
            });

            units.value = data.units || [];
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar unidades");
        } finally {
            loading.value = false;
        }
    }

    function open(data = {}) {
        productId.value = data.id ?? data.productId ?? null;
        companyId.value = data.company_id ?? data.companyId ?? null;
        productName.value = data.name ?? "";
        isOpen.value = true;

        loadUnits();
    }

    function handleClose() {
        units.value = [];
        productId.value = null;
        companyId.value = null;
        productName.value = "";
    }

    defineExpose({
        open,
        loadUnits,
    });
</script>
