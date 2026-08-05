<template>
    <Container title="Sessões" gapped>
        <SearchField
            :fields="searchFields"
            @refresh="refresh"
            v-model="search"
        ></SearchField>

        <VirtualRemoteTable
            ref="tableRef"
            items-key="sessions"
            :href="route('api.sessions.index')"
            :headers="headers"
            :filters="filters"
        >
            <template #item.last_activity="{ item }">
                <span v-if="!!item.last_activity">
                    {{ formatDate(item.last_activity, "dd/MM/yyyy HH:mm") }}
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

    import { formatDate } from "@/Utils/Common";

    const tableRef = ref(null);
    const search = ref(null);

    const searchFields = ref([
        {
            key: "ip_address",
            title: "IP",
        },
    ]);

    const headers = ref([
        {
            key: "ip_address",
            title: "IP",
        },
        {
            key: "user_name",
            title: "Usuário",
        },
        {
            key: "user_email",
            title: "Email",
        },
        {
            key: "browser",
            title: "Navegador",
        },
        {
            key: "os",
            title: "Sistema Operacional",
        },
        {
            key: "device",
            title: "Dispositivo",
        },
        {
            key: "last_activity",
            title: "Última Atividade",
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
