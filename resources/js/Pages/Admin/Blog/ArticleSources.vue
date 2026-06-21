<template>
    <Container title="Fontes de Artigos" gapped>
        <v-card hover border>
            <v-text-field
                type="search"
                placeholder="Pesquisar"
                prepend-inner-icon="mdi-magnify"
                append-inner-icon="mdi-refresh"
                variant="solo"
                clearable
                @keyup.enter="handleSearch"
                @click:append-inner="getArticleSources"
                v-model="search"
            ></v-text-field>
        </v-card>

        <v-card>
            <v-data-table-server
                :headers="headers"
                :items="articleSources"
                :items-length="pagination.total"
                :loading="loading"
                class="text-no-wrap"
                @update:options="getArticleSources"
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

        <ArticleSourceForm
            ref="articleSourceFormRef"
            @form-closed="getArticleSources"
        ></ArticleSourceForm>

        <v-fab
            app
            icon="mdi-plus"
            size="large"
            color="primary"
            @click="articleSourceFormRef.open()"
        ></v-fab>
    </Container>
</template>

<script setup>
    import { ref, reactive } from "vue";

    import api from "@/api";

    import Container from "@/Components/Container.vue";
    import ArticleSourceForm from "@/Components/Admin/Blog/ArticleSourceForm.vue";

    import { alertError } from "@/Utils/Alert";
    import { formatDate } from "@/Utils/Common";

    const articleSourceFormRef = ref(null);

    const search = ref("");
    const articleSources = ref([]);
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

    async function getArticleSources() {
        loading.value = true;

        try {
            const url = route("api.article-sources.index");

            const params = {
                page: pagination.page,
                per_page: pagination.perPage,
            };

            const { data } = await api.get(url, { params });

            articleSources.value = data.article_sources;

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
        await getArticleSources();
    }

    function handleRowClick(e, { item }) {
        articleSourceFormRef.value.open(item);
    }
</script>
