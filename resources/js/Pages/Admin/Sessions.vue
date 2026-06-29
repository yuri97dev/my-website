<template>
    <Container title="Sessões" gapped>
        <SearchField
            :fields="searchFields"
            @refresh="getSessions"
            @update:model-value="handleSearch"
            v-model="search"
        ></SearchField>

        <v-card>
            <v-data-table-server
                :headers="headers"
                :items="sessions"
                :items-length="pagination.total"
                :loading="loading"
                class="text-no-wrap"
                @update:options="getSessions"
                v-model:page="pagination.page"
                v-model:items-per-page="pagination.perPage"
            >
                <template #item.last_activity="{ item }">
                    <span v-if="!!item.last_activity">
                        {{ formatDate(item.last_activity, "dd/MM/yyyy HH:mm") }}
                    </span>
                </template>
            </v-data-table-server>
        </v-card>
    </Container>
</template>

<script setup>
    import { ref, reactive, nextTick } from "vue";

    import Container from "@/Components/Container.vue";
    import SearchField from "@/Components/SearchField.vue";

    import api from "@/api";

    import { alertError } from "@/Utils/Alert";
    import { formatDate } from "@/Utils/Common";

    const search = ref(null);
    const sessions = ref([]);
    const loading = ref(false);

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

    const pagination = reactive({
        page: 1,
        perPage: 10,
        total: 0,
    });

    async function getSessions() {
        loading.value = true;

        try {
            const url = route("api.sessions.index");

            const params = {
                page: pagination.page,
                per_page: pagination.perPage,
            };

            if( search.value ) {
                const { key, search: searchValue } = search.value || {};
                params[key] = searchValue;
            }

            const { data } = await api.get(url, { params });

            sessions.value = data.sessions;

            pagination.page = data.current_page;
            pagination.perPage = data.per_page;
            pagination.total = data.total;
        } catch (e) {
            alertError("Ooops", "Erro ao buscar sessões");
        }

        loading.value = false;
    }

    function handleSearch() {
        pagination.page = 1;

        nextTick(async () => {
            await getSessions();
        });
    }
</script>
