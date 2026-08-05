<template>
    <Container title="Perfis HTTP" gapped>
        <VirtualRemoteTable
            ref="tableRef"
            items-key="scrape_http_profiles"
            :href="route('api.scrape-http-profiles.index')"
            :headers="headers"
            :row-props="rowProps"
        >
            <template #item.headers_count="{ item }">
                {{ Object.keys(item.headers || {}).length }}
            </template>

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

        <ScrapeHttpProfileForm
            ref="scrapeHttpProfileFormRef"
            @form-closed="refresh"
        ></ScrapeHttpProfileForm>

        <v-fab
            app
            icon="mdi-plus"
            size="large"
            color="primary"
            @click="scrapeHttpProfileFormRef.open()"
        ></v-fab>
    </Container>
</template>

<script setup>
    import { ref } from "vue";

    import Container from "@/Components/Container.vue";
    import ScrapeHttpProfileForm from "@/Components/Admin/ScrapeHttpProfileForm.vue";
    import VirtualRemoteTable from "@/Components/VirtualRemoteTable.vue";

    import { formatDate } from "@/Utils/Common";

    const scrapeHttpProfileFormRef = ref(null);
    const tableRef = ref(null);

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
            key: "headers_count",
            title: "Headers",
            sortable: false,
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

    function rowProps({ item }) {
        return {
            style: { cursor: "pointer" },
            onDblclick: () => scrapeHttpProfileFormRef.value.open(item),
        };
    }

    function refresh() {
        tableRef.value?.fetchItems(1);
    }
</script>
