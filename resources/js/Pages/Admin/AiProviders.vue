<template>
    <Container title="Provedores de IA" gapped>
        <v-card hover border>
            <v-text-field
                type="search"
                placeholder="Pesquisar"
                prepend-inner-icon="mdi-magnify"
                append-inner-icon="mdi-refresh"
                variant="solo"
                clearable
                @keyup.enter="handleSearch"
                @click:append-inner="getAiProviders"
                v-model="search"
            ></v-text-field>
        </v-card>

        <v-card>
            <v-data-table-server
                :headers="headers"
                :items="aiProviders"
                :items-length="pagination.total"
                :loading="loading"
                class="text-no-wrap"
                @update:options="getAiProviders"
                @click:row="handleRowClick"
                v-model:page="pagination.page"
                v-model:items-per-page="pagination.perPage"
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
            </v-data-table-server>
        </v-card>

        <AiProviderForm
            ref="aiProviderFormRef"
            @form-closed="getAiProviders"
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
    import { ref, reactive } from "vue";

    import api from "@/api";

    import Container from "@/Components/Container.vue";
    import AiProviderForm from "@/Components/Admin/AiProviderForm.vue";

    import { alertError } from "@/Utils/Alert";
    import { formatDate } from "@/Utils/Common";

    const aiProviderFormRef = ref(null);

    const search = ref("");
    const aiProviders = ref([]);
    const loading = ref(false);

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
            key: "url",
            title: "URL",
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

    const pagination = reactive({
        page: 1,
        perPage: 10,
        total: 0,
    });

    async function getAiProviders() {
        loading.value = true;

        try {
            const url = route("api.ai-providers.index");

            const params = {
                page: pagination.page,
                per_page: pagination.perPage,
            };

            const { data } = await api.get(url, { params });

            aiProviders.value = data.ai_providers;

            pagination.page = data.current_page;
            pagination.perPage = data.per_page;
            pagination.total = data.total;
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar provedores de IA");
        }

        loading.value = false;
    }

    async function handleSearch() {
        pagination.page = 1;
        await getAiProviders();
    }

    function handleRowClick(e, { item }) {
        aiProviderFormRef.value.open(item);
    }
</script>
