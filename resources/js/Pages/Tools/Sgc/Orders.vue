<template>
    <Container title="Pedidos" gapped>
        <SearchField
            :fields="searchFields"
            @refresh="refresh"
            v-model="search"
        ></SearchField>

        <VirtualRemoteTable
            ref="tableRef"
            items-key="orders"
            :href="route('api.tools.sgc.orders.index')"
            :headers="headers"
            :filters="filters"
            :row-props="rowProps"
        >
            <template #item.status="{ item }">
                <v-chip
                    size="small"
                    :color="statusColor(item.status)"
                    variant="tonal"
                >
                    {{ statusLabel(item.status) }}
                </v-chip>
            </template>

            <template #item.total="{ item }">
                <span v-if="item.total != null">
                    R$ {{ formatMoney(item.total) }}
                </span>
            </template>

            <template #item.ordered_at="{ item }">
                <span v-if="!!item.ordered_at">
                    {{ formatDate(item.ordered_at, "dd/MM/yyyy HH:mm") }}
                </span>
            </template>

            <template #item.created_at="{ item }">
                <span v-if="!!item.created_at">
                    {{ formatDate(item.created_at, "dd/MM/yyyy HH:mm") }}
                </span>
            </template>
        </VirtualRemoteTable>

        <OrderForm
            ref="orderFormRef"
            @form-closed="refresh"
        ></OrderForm>

        <OrderDetails
            ref="orderDetailsRef"
            @form-closed="refresh"
            @updated="refresh"
        ></OrderDetails>

        <v-fab
            app
            icon="mdi-plus"
            size="large"
            color="primary"
            @click="orderFormRef.open()"
        ></v-fab>
    </Container>
</template>

<script setup>
    import { ref, computed } from "vue";

    import Container from "@/Components/Container.vue";
    import SearchField from "@/Components/SearchField.vue";
    import OrderDetails from "@/Components/Tools/Sgc/OrderDetails.vue";
    import OrderForm from "@/Components/Tools/Sgc/OrderForm.vue";
    import VirtualRemoteTable from "@/Components/VirtualRemoteTable.vue";

    import { formatDate } from "@/Utils/Common";
    import { formatMoney } from "@/Utils/Money";

    const orderFormRef = ref(null);
    const orderDetailsRef = ref(null);
    const tableRef = ref(null);
    const search = ref(null);

    const statusLabels = {
        draft: "Rascunho",
        confirmed: "Confirmado",
        cancelled: "Cancelado",
    };

    const statusColors = {
        draft: "grey",
        confirmed: "success",
        cancelled: "error",
    };

    const searchFields = ref([
        {
            key: "id",
            title: "ID",
        },
        {
            key: "company",
            title: "Empresa",
        },
        {
            key: "customer",
            title: "Cliente",
        },
        {
            key: "status",
            title: "Status",
        },
    ]);

    const headers = ref([
        {
            key: "id",
            title: "ID",
        },
        {
            key: "status",
            title: "Status",
        },
        {
            key: "company_name",
            title: "Empresa",
        },
        {
            key: "customer_name",
            title: "Cliente",
        },
        {
            key: "total",
            title: "Total",
        },
        {
            key: "created_by_name",
            title: "Criado Por",
        },
        {
            key: "ordered_at",
            title: "Data do Pedido",
        },
        {
            key: "created_at",
            title: "Criado Em",
        },
    ]);

    const filters = computed(() => {
        if( !search.value ) return {};

        const { key, search: searchValue } = search.value;

        return {
            [key]: searchValue,
        };
    });

    function statusLabel(status) {
        return statusLabels[status] || status;
    }

    function statusColor(status) {
        return statusColors[status] || "default";
    }

    function rowProps({ item }) {
        return {
            style: { cursor: "pointer" },
            onDblclick: () => orderDetailsRef.value.open(item),
        };
    }

    function refresh() {
        tableRef.value?.fetchItems(1);
    }
</script>
