<template>
    <Container title="Provedores de IA" gapped>
        <SearchField
            :fields="searchFields"
            @refresh="refresh"
            v-model="search"
        ></SearchField>

        <VirtualRemoteTable
            ref="tableRef"
            items-key="ai_providers"
            :href="route('api.ai-providers.index')"
            :headers="headers"
            :filters="filters"
            :row-props="rowProps"
        >
            <template #item.is_enabled="{ item }">
                <v-icon
                    :icon="item.is_enabled ? 'mdi-check-circle' : 'mdi-close-circle'"
                    :color="item.is_enabled ? 'success' : 'error'"
                ></v-icon>
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

        <AiProviderForm
            ref="aiProviderFormRef"
            @form-closed="refresh"
        ></AiProviderForm>

        <v-fab
            app
            icon="mdi-plus"
            size="large"
            color="primary"
            @click="aiProviderFormRef.open()"
        ></v-fab>
    </Container>
</template>

<script setup>
    import { ref, computed } from "vue";

    import Container from "@/Components/Container.vue";
    import AiProviderForm from "@/Components/Admin/AiProviderForm.vue";
    import SearchField from "@/Components/SearchField.vue";
    import VirtualRemoteTable from "@/Components/VirtualRemoteTable.vue";

    import { formatDate } from "@/Utils/Common";

    const aiProviderFormRef = ref(null);
    const tableRef = ref(null);
    const search = ref(null);

    const searchFields = ref([
        {
            key: "id",
            title: "ID",
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
            key: "provider",
            title: "Provedor",
        },
        {
            key: "is_enabled",
            title: "Ativo",
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
            onDblclick: () => aiProviderFormRef.value.open(item),
        };
    }

    function refresh() {
        tableRef.value?.fetchItems(1);
    }
</script>
