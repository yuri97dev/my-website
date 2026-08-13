<template>
    <Container title="Clientes" gapped>
        <SearchField
            :fields="searchFields"
            @refresh="refresh"
            v-model="search"
        ></SearchField>

        <VirtualRemoteTable
            ref="tableRef"
            items-key="customers"
            :href="route('api.tools.sgc.customers.index')"
            :headers="headers"
            :filters="filters"
            :row-props="rowProps"
        >
            <template #item.birth_date="{ item }">
                <span v-if="!!item.birth_date">
                    {{ formatDate(item.birth_date, "dd/MM/yyyy") }}
                </span>
            </template>

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

        <CustomerForm
            ref="customerFormRef"
            @form-closed="refresh"
        ></CustomerForm>

        <v-fab
            app
            icon="mdi-plus"
            size="large"
            color="primary"
            @click="customerFormRef.open()"
        ></v-fab>
    </Container>
</template>

<script setup>
    import { ref, computed } from "vue";

    import Container from "@/Components/Container.vue";
    import SearchField from "@/Components/SearchField.vue";
    import CustomerForm from "@/Components/Tools/Sgc/CustomerForm.vue";
    import VirtualRemoteTable from "@/Components/VirtualRemoteTable.vue";

    import { formatDate } from "@/Utils/Common";

    const customerFormRef = ref(null);
    const tableRef = ref(null);
    const search = ref(null);

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
            key: "name",
            title: "Nome",
        },
        {
            key: "nickname",
            title: "Apelido",
        },
        {
            key: "tax_id",
            title: "CPF/CNPJ",
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
            key: "nickname",
            title: "Apelido",
        },
        {
            key: "tax_id",
            title: "CPF/CNPJ",
        },
        {
            key: "birth_date",
            title: "Nascimento",
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

    function rowProps({ item }) {
        return {
            style: { cursor: "pointer" },
            onDblclick: () => customerFormRef.value.open(item),
        };
    }

    function refresh() {
        tableRef.value?.fetchItems(1);
    }
</script>
