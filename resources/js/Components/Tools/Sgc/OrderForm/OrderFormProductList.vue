<template>
    <v-alert
        v-if="!companyId"
        type="info"
        variant="tonal"
        text="Selecione uma empresa para listar os produtos."
    ></v-alert>

    <template v-else>
        <v-row>
            <v-col cols="12" md="8">
                <v-text-field
                    label="Buscar produto"
                    placeholder="Pressione Enter para buscar"
                    prepend-inner-icon="mdi-magnify"
                    clearable
                    hide-details
                    v-model="search"
                    @keyup.enter="submitSearch"
                    @click:clear="clearSearch"
                >
                    <template #append-inner>
                        <v-btn
                            size="small"
                            icon="mdi-magnify"
                            variant="tonal"
                            color="primary"
                            class="rounded"
                            :loading="loading"
                            aria-label="Buscar"
                            @click.prevent="submitSearch"
                        ></v-btn>
                    </template>
                </v-text-field>
            </v-col>

            <v-col cols="12" md="4">
                <v-select
                    label="Estoque"
                    hide-details
                    :items="stockFilterOptions"
                    v-model="stockFilter"
                ></v-select>
            </v-col>
        </v-row>

        <v-divider class="my-4"></v-divider>

        <v-progress-linear
            v-if="loading"
            indeterminate
            color="primary"
            class="mt-4"
        ></v-progress-linear>

        <v-row
            v-else-if="products.length > 0"
            class="mt-4"
        >
            <v-col
                v-for="product in products"
                :key="product.company_product_id"
                cols="12"
                lg="6"
            >
                <v-card
                    border
                    variant="tonal"
                    class="h-100 d-flex flex-column"
                >
                    <v-card-title class="text-truncate lines-2">
                        {{ product.name }}
                    </v-card-title>

                    <v-card-subtitle
                        v-if="product.serial_number"
                        class="text-wrap opacity-100 pb-0"
                    >
                        <span class="d-inline-flex align-center ga-2">
                            <v-icon
                                icon="mdi-barcode"
                                size="small"
                            ></v-icon>

                            {{ product.serial_number }}
                        </span>
                    </v-card-subtitle>

                    <v-card-text class="text-body-2 text-medium-emphasis pt-2">
                        {{ productSubtitle(product) }}
                    </v-card-text>

                    <v-card-actions class="pt-0">
                        <v-number-input
                            label="Quantidade"
                            hide-details
                            control-variant="split"
                            :min="0"
                            :max="Number(product.stock_quantity || 0)"
                            :disabled="!hasStock(product)"
                            :suffix="product.unit_of_measure || undefined"
                            :model-value="cartQuantity(product.company_product_id)"
                            @update:model-value="setQuantity(product, $event)"
                        ></v-number-input>
                    </v-card-actions>
                </v-card>
            </v-col>
        </v-row>

        <div v-else>
            <v-alert v-if="!hasFetched" type="info" variant="tonal">
                Digite na busca e pressione Enter, ou altere o filtro de estoque para listar produtos.
            </v-alert>

            <v-alert v-else type="warning" variant="tonal">
                Nenhum resultado encontrado.
                Tente outro termo de busca ou altere o filtro de estoque.
            </v-alert>
        </div>
    </template>
</template>

<script setup>
    import { ref, watch } from "vue";

    import api from "@/api";

    import { formatNumber } from "@/Utils/Common";
    import { formatMoney } from "@/Utils/Money";
    import { alertError } from "@/Utils/Alert";

    const props = defineProps({
        companyId: {
            type: [ Number, null ],
            default: null,
        },
        cart: {
            type: Object,
            required: true,
        },
    });

    const emit = defineEmits([
        "set-quantity",
    ]);

    const products = ref([]);
    const search = ref("");
    const appliedSearch = ref("");
    const stockFilter = ref("in_stock");
    const loading = ref(false);
    const hasFetched = ref(false);
    let suppressStockFilterFetch = false;

    const stockFilterOptions = [
        { title: "EXIBIR TUDO", value: "all" },
        { title: "COM ESTOQUE", value: "in_stock" },
        { title: "SEM ESTOQUE", value: "out_of_stock" },
    ];

    function hasStock(product) {
        return Number(product.stock_quantity || 0) > 0;
    }

    function cartQuantity(companyProductId) {
        return Number(props.cart[companyProductId]?.quantity || 0);
    }

    function productSubtitle(product) {
        const parts = [];

        if( product.brand_name ) {
            parts.push(product.brand_name);
        }

        if( product.sale_price != null ) {
            parts.push(`R$ ${formatMoney(product.sale_price)}`);
        }

        parts.push(`Estoque: ${formatNumber(product.stock_quantity || 0, 3)}`);

        if( product.unit_of_measure ) {
            parts.push(product.unit_of_measure);
        }

        return parts.join(" · ");
    }

    function setQuantity(product, quantity) {
        emit("set-quantity", { product, quantity });
    }

    function submitSearch() {
        appliedSearch.value = search.value?.trim() || "";
        fetchProducts();
    }

    function clearSearch() {
        search.value = "";
        appliedSearch.value = "";

        if( hasFetched.value ) {
            fetchProducts();
        }
    }

    function buildFetchParams() {
        const params = {
            company_id: props.companyId,
            stock: stockFilter.value,
            per_page: 100,
            page: 1,
        };

        if( appliedSearch.value ) {
            params.search = appliedSearch.value;
        }

        return params;
    }

    async function fetchProducts() {
        if( !props.companyId ) {
            products.value = [];
            hasFetched.value = false;
            return;
        }

        loading.value = true;

        try {
            const url = route("api.tools.sgc.company-products.index");

            const { data } = await api.get(url, {
                params: buildFetchParams(),
            });

            products.value = data.company_products || [];
            hasFetched.value = true;
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar produtos");
        } finally {
            loading.value = false;
        }
    }

    watch(stockFilter, () => {
        if( suppressStockFilterFetch || !props.companyId ) {
            return;
        }

        fetchProducts();
    });

    watch(() => props.companyId, () => {
        suppressStockFilterFetch = true;

        products.value = [];
        search.value = "";
        appliedSearch.value = "";
        stockFilter.value = "all";
        hasFetched.value = false;

        suppressStockFilterFetch = false;
    });
</script>
