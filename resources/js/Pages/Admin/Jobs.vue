<template>
    <Container title="Jobs" gapped>
        <SearchField
            @refresh="refresh"
            v-model="search"
        ></SearchField>

        <VirtualRemoteTable
            ref="tableRef"
            items-key="jobs"
            :href="route('api.jobs.spreadsheet-formatter')"
            :headers="headers"
            :filters="filters"
        >
            <template #item.size_bytes="{ item }">
                {{ formatBytes(item.size_bytes) }}
            </template>

            <template #item.progress="{ item }">
                <v-progress-linear
                    rounded
                    color="primary"
                    :height="10"
                    :indeterminate="item.progress === 0"
                    :model-value="item.progress"
                ></v-progress-linear>
            </template>

            <template #item.created_at="{ item }">
                {{ formatDate(item.created_at, "dd/MM/yyyy HH:mm") }}
            </template>

            <template #item.updated_at="{ item }">
                {{ formatDate(item.updated_at, "dd/MM/yyyy HH:mm") }}
            </template>

            <template #item.expires_at="{ item }">
                <span v-if="!!item.expires_at">
                    {{ formatDate(item.expires_at, "dd/MM/yyyy HH:mm") }}
                </span>
            </template>

            <template #item.file_removed_at="{ item }">
                <span v-if="!!item.file_removed_at">
                    {{ formatDate(item.file_removed_at, "dd/MM/yyyy HH:mm") }}
                </span>
            </template>
        </VirtualRemoteTable>
    </Container>
</template>

<script setup>
    import { ref, computed } from "vue";

    import Container from "@/Components/Container.vue";
    import SearchField from "@/Components/SearchField.vue";
    import VirtualRemoteTable from "@/Components/VirtualRemoteTable.vue";

    import { formatBytes, formatDate } from "@/Utils/Common";

    const tableRef = ref(null);
    const search = ref(null);

    const headers = ref([
        {
            key: "user.id",
            title: "Usuário.Id",
        },
        {
            key: "user.name",
            title: "Usuário.Nome",
        },
        {
            key: "status",
            title: "Status",
        },
        {
            key: "progress",
            title: "Progresso",
        },
        {
            key: "size_bytes",
            title: "Tamanho",
        },
        {
            key: "created_at",
            title: "Criado Em",
        },
        {
            key: "updated_at",
            title: "Atualizado Em",
        },
        {
            key: "expires_at",
            title: "Expira Em",
        },
        {
            key: "file_removed_at",
            title: "Removido Em",
        },
    ]);

    const filters = computed(() => {
        if( !search.value ) return {};

        const { key, search: searchValue } = search.value;

        return {
            [key]: searchValue,
        };
    });

    function refresh() {
        tableRef.value?.fetchItems(1);
    }
</script>
