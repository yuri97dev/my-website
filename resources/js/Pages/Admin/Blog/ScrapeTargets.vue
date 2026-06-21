<template>
    <Container title="Scrape Targets" gapped>
        <v-card hover border>
            <v-text-field
                type="search"
                placeholder="Pesquisar"
                prepend-inner-icon="mdi-magnify"
                append-inner-icon="mdi-refresh"
                variant="solo"
                clearable
                @keyup.enter="handleSearch"
                @click:append-inner="getScrapeTargets"
                v-model="search"
            ></v-text-field>
        </v-card>

        <v-card>
            <v-data-table-server
                :headers="headers"
                :items="scrapeTargets"
                :items-length="pagination.total"
                :loading="loading"
                class="text-no-wrap"
                @update:options="getScrapeTargets"
                @click:row="handleRowClick"
                v-model:page="pagination.page"
                v-model:items-per-page="pagination.perPage"
            >
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

        <ScrapeForm
            ref="scrapeFormRef"
            @form-closed="getScrapeTargets"
        ></ScrapeForm>

        <v-fab
            app
            icon="mdi-plus"
            size="large"
            color="primary"
            @click="scrapeFormRef.open()"
        ></v-fab>
    </Container>
</template>

<script setup>
    import { ref, reactive } from "vue";

    import api from "@/api";

    import Container from "@/Components/Container.vue";
    import ScrapeForm from "@/Components/Admin/Blog/ScrapeForm.vue";

    import { alertError } from "@/Utils/Alert";
    import { formatDate } from "@/Utils/Common";

    const scrapeFormRef = ref(null);

    const search = ref("");
    const scrapeTargets = ref([]);
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
            key: "driver",
            title: "Driver",
        },
        {
            key: "locale",
            title: "Locale",
        },
        {
            key: "http_method",
            title: "Método HTTP",
        },
        {
            key: "list_url",
            title: "URL da Lista",
        },
        {
            key: "list_item_selector",
            title: "Selector da Lista",
        },
        {
            key: "item_title_selector",
            title: "Selector do Título",
        },
        {
            key: "item_link_selector",
            title: "Selector do Link",
        },
        {
            key: "content_selector",
            title: "Selector do Conteúdo",
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

    async function getScrapeTargets() {
        loading.value = true;

        try {
            const url = route("api.scrape-targets.index");

            const params = {
                page: pagination.page,
                per_page: pagination.perPage,
            };

            const { data } = await api.get(url, { params });

            scrapeTargets.value = data.scrape_targets;

            pagination.page = data.current_page;
            pagination.perPage = data.per_page;
            pagination.total = data.total;
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar sites");
        }

        loading.value = false;
    }

    async function handleSearch() {
        pagination.page = 1;
        await getScrapeTargets();
    }

    function handleRowClick(e, { item }) {
        scrapeFormRef.value.open(item);
    }
</script>
