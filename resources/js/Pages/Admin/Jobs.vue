<template>
    <Container>
        <v-card hover border class="mb-4">
            <v-text-field
                type="search"
                placeholder="Pesquisar"
                prepend-inner-icon="mdi-magnify"
                append-inner-icon="mdi-refresh"
                variant="solo"
                clearable
                @keyup.enter="handleSearch"
                @click:append-inner="getJobs"
                v-model="search"
            ></v-text-field>
        </v-card>

        <v-card>
            <v-data-table-server
                :headers="headers"
                :items="jobs"
                :items-length="pagination.total"
                :loading="loading"
                class="text-no-wrap"
                @update:options="getJobs"
                v-model:page="pagination.page"
                v-model:items-per-page="pagination.perPage"
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
                    {{ format(item.created_at, "dd/MM/yyyy HH:mm") }}
                </template>

                <template #item.updated_at="{ item }">
                    {{ format(item.updated_at, "dd/MM/yyyy HH:mm") }}
                </template>

                <template #item.expires_at="{ item }">
                    <span v-if="!!item.expires_at">
                        {{ format(item.expires_at, "dd/MM/yyyy HH:mm") }}
                    </span>
                </template>

                <template #item.file_removed_at="{ item }">
                    <span v-if="!!item.file_removed_at">
                        {{ format(item.file_removed_at, "dd/MM/yyyy HH:mm") }}
                    </span>
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
    import { formatBytes } from "@/Utils/Common";

    const search = ref("");
    const jobs = ref([]);
    const loading = ref(false);

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

    const pagination = reactive({
        page: 1,
        perPage: 10,
        total: 0,
    });

    async function getJobs() {
        loading.value = true;

        try {
            const url = route("api.jobs.spreadsheet-formatter");

            const params = {
                search: search.value,
                page: pagination.page,
                per_page: pagination.perPage,
            };

            const { data } = await api.get(url, { params });

            jobs.value = data.jobs;

            pagination.page = data.current_page;
            pagination.perPage = data.per_page;
            pagination.total = data.total;
        } catch (e) {
            alertError("Ooops", "Erro ao buscar jobs de formatação de planilhas");
        }

        loading.value = false;
    }

    async function handleSearch() {
        pagination.page = 1;
        await getJobs();
    }
</script>
