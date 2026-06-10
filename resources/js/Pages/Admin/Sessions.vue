<template>
    <Container>
        <v-card>
            <v-card-item>
                <v-text-field
                    type="search"
                    label="Pesquisar"
                    prepend-inner-icon="mdi-magnify"
                    append-inner-icon="mdi-refresh"
                    clearable
                    @keyup.enter="handleSearch"
                    @click:append-inner="getSessions"
                    v-model="search"
                ></v-text-field>
            </v-card-item>

            <v-divider></v-divider>

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
                    {{ format(item.last_activity, "dd/MM/yyyy HH:mm") }}
                </template>
            </v-data-table-server>
        </v-card>
    </Container>
</template>

<script setup>
    import { ref, reactive, onMounted } from "vue";
    import { format } from "date-fns";

    import Container from "@/Components/Container.vue";

    import api from "@/api";

    import { alertError } from "@/Utils/Alert";

    const search = ref("");
    const sessions = ref([]);
    const loading = ref(false);

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
                search: search.value,
                page: pagination.page,
                per_page: pagination.perPage,
            };

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

    async function handleSearch() {
        pagination.page = 1;
        await getSessions();
    }
</script>
