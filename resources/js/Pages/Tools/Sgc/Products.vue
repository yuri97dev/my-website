<template>
    <Container title="Produtos" gapped>
        <SearchField
            :fields="searchFields"
            @refresh="refresh"
            v-model="search"
        ></SearchField>

        <VirtualRemoteTable
            ref="tableRef"
            items-key="products"
            :href="route('api.tools.sgc.products.index')"
            :headers="headers"
            :filters="filters"
            :row-props="rowProps"
            :prepare-item="prepareItem"
        >
            <template #item.created_at="{ item }">
                <span v-if="!!item.created_at">
                    {{ formatDate(item.created_at, "dd/MM/yyyy HH:mm") }}
                </span>
            </template>

            <template #item.updated_at="{ item }">
                <span v-if="!!item.updated_at">
                    {{ formatDate(item.updated_at, "dd/MM/yyyy HH:mm") }}
                </span>
            </template>
        </VirtualRemoteTable>

        <ProductForm
            ref="productFormRef"
            @form-closed="refresh"
        ></ProductForm>

        <v-fab
            app
            icon="mdi-plus"
            size="large"
            color="primary"
            @click="productFormRef.open()"
        ></v-fab>
    </Container>
</template>

<script setup>
    import { ref, computed } from "vue";

    import Container from "@/Components/Container.vue";
    import SearchField from "@/Components/SearchField.vue";
    import ProductForm from "@/Components/Tools/Sgc/ProductForm.vue";
    import VirtualRemoteTable from "@/Components/VirtualRemoteTable.vue";

    import { formatDate } from "@/Utils/Common";

    const productFormRef = ref(null);
    const tableRef = ref(null);
    const search = ref(null);

    const searchFields = ref([
        {
            key: "id",
            title: "ID",
        },
        {
            key: "company_id",
            title: "Empresa",
        },
        {
            key: "name",
            title: "Nome",
        },
    ]);

    const headers = ref([
        {
            key: "id",
            title: "ID",
        },
        {
            key: "name",
            title: "Nome",
        },
        {
            key: "brand_name",
            title: "Marca",
        },
        {
            key: "category_name",
            title: "Categoria",
        },
        {
            key: "created_at",
            title: "Criado Em",
        },
        {
            key: "updated_at",
            title: "Atualizado Em",
        },
    ]);

    const filters = computed(() => {
        if( !search.value ) return {};

        const { key, search: searchValue } = search.value;

        return {
            [key]: searchValue,
        };
    });

    function prepareItem(item) {
        return {
            ...item,
            brand_name: item.brand?.name,
            category_name: item.category?.name,
        };
    }

    function rowProps({ item }) {
        return {
            style: { cursor: "pointer" },
            onDblclick: () => productFormRef.value.open(item),
        };
    }

    function refresh() {
        tableRef.value?.fetchItems(1);
    }
</script>
