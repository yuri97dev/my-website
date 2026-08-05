<template>
    <Container title="Usuários" gapped>
        <SearchField
            :fields="searchFields"
            @refresh="refresh"
            v-model="search"
        ></SearchField>

        <VirtualRemoteTable
            ref="tableRef"
            items-key="users"
            :href="route('api.users.index')"
            :headers="headers"
            :filters="filters"
        >
            <template #item.roles="{ item }">
                <v-chip
                    v-for="role in item.roles"
                    :key="role.id"
                    color="primary"
                    variant="outlined"
                >{{ role.name }}</v-chip>
            </template>

            <template #item.created_at="{ item }">
                {{ formatDate(item.created_at, "dd/MM/yyyy HH:mm") }}
            </template>

            <template #item.updated_at="{ item }">
                {{ formatDate(item.updated_at, "dd/MM/yyyy HH:mm") }}
            </template>
        </VirtualRemoteTable>
    </Container>
</template>

<script setup>
    import { ref, computed } from "vue";

    import Container from "@/Components/Container.vue";
    import SearchField from "@/Components/SearchField.vue";
    import VirtualRemoteTable from "@/Components/VirtualRemoteTable.vue";

    import { formatDate } from "@/Utils/Common";

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
        {
            key: "email",
            title: "Email",
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
            key: "email",
            title: "Email",
        },
        {
            key: "roles",
            title: "Funções",
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

    function refresh() {
        tableRef.value?.fetchItems(1);
    }
</script>
