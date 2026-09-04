<template>
    <Container title="Empresas" gapped>
        <VirtualRemoteTable
            ref="tableRef"
            items-key="companies"
            :href="route('api.tools.sgc.companies.index')"
            :headers="headers"
            :row-props="rowProps"
            :prepare-item="prepareItem"
        >
            <template #item.label="{ item }">
                {{ item.label }}
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

        <CompanyForm
            ref="companyFormRef"
            @form-closed="refresh"
        ></CompanyForm>

        <v-fab
            app
            icon="mdi-plus"
            size="large"
            color="primary"
            @click="companyFormRef.open()"
        ></v-fab>
    </Container>
</template>

<script setup>
    import { ref } from "vue";

    import Container from "@/Components/Container.vue";
    import CompanyForm from "@/Components/Tools/Sgc/CompanyForm.vue";
    import VirtualRemoteTable from "@/Components/VirtualRemoteTable.vue";

    import { formatDate } from "@/Utils/Common";
    import { registerSgcTabs } from "@/Data/sgc";

    const companyFormRef = ref(null);
    const tableRef = ref(null);

    const headers = ref([
        {
            key: "id",
            title: "ID",
        },
        {
            key: "label",
            title: "Nome",
        },
        {
            key: "document",
            title: "Documento",
        },
        {
            key: "address_city",
            title: "Cidade",
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

    function prepareItem(item) {
        return {
            ...item,
            label: item.trade_name || item.legal_name,
        };
    }

    function rowProps({ item }) {
        return {
            style: { cursor: "pointer" },
            onDblclick: () => companyFormRef.value.open(item),
        };
    }

    function refresh() {
        tableRef.value?.fetchItems(1);
    }

    registerSgcTabs();
</script>
