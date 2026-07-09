<template>
    <Container title="Perfis HTTP" gapped>
        <v-card>
            <v-data-table-server
                :headers="headers"
                :items="scrapeHttpProfiles"
                :items-length="pagination.total"
                :loading="loading"
                class="text-no-wrap"
                @update:options="getScrapeHttpProfiles"
                @click:row="handleRowClick"
                v-model:page="pagination.page"
                v-model:items-per-page="pagination.perPage"
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
            </v-data-table-server>
        </v-card>

        <ScrapeHttpProfileForm
            ref="scrapeHttpProfileFormRef"
            @form-closed="getScrapeHttpProfiles"
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
    import { ref, reactive } from "vue";

    import api from "@/api";

    import Container from "@/Components/Container.vue";
    import ScrapeHttpProfileForm from "@/Components/Admin/ScrapeHttpProfileForm.vue";

    import { alertError } from "@/Utils/Alert";
    import { formatDate } from "@/Utils/Common";

    const scrapeHttpProfileFormRef = ref(null);

    const scrapeHttpProfiles = ref([]);
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

    const pagination = reactive({
        page: 1,
        perPage: 10,
        total: 0,
    });

    async function getScrapeHttpProfiles() {
        loading.value = true;

        try {
            const url = route("api.scrape-http-profiles.index");

            const params = {
                page: pagination.page,
                per_page: pagination.perPage,
            };

            const { data } = await api.get(url, { params });

            scrapeHttpProfiles.value = data.scrape_http_profiles;

            pagination.page = data.current_page;
            pagination.perPage = data.per_page;
            pagination.total = data.total;
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar perfis HTTP");
        }

        loading.value = false;
    }

    function handleRowClick(e, { item }) {
        scrapeHttpProfileFormRef.value.open(item);
    }
</script>
