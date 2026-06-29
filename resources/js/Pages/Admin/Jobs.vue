<template>
    <Container title="Jobs" gapped>
        <SearchField
            @refresh="getJobs"
            @update:model-value="handleSearch"
            v-model="search"
        ></SearchField>

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
    import { formatBytes, formatDate } from "@/Utils/Common";

    const search = ref(null);
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
                page: pagination.page,
                per_page: pagination.perPage,
            };

            if( search.value ) {
                const { key, search: searchValue } = search.value || {};
                params[key] = searchValue;
            }

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

    function handleSearch() {
        pagination.page = 1;

        nextTick(async () => {
            await getJobs();
        });
    }
</script>
