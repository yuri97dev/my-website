<template>
    <Container title="Fontes de Artigos" gapped>
        <SearchField
            @refresh="refresh"
            v-model="search"
        ></SearchField>

        <VirtualRemoteTable
            ref="tableRef"
            items-key="article_sources"
            :href="route('api.article-sources.index')"
            :headers="headers"
            :filters="filters"
            :row-props="rowProps"
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
        </VirtualRemoteTable>

        <ArticleSourceForm
            ref="articleSourceFormRef"
            @form-closed="refresh"
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
    import { ref, computed } from "vue";

    import Container from "@/Components/Container.vue";
    import ArticleSourceForm from "@/Components/Admin/Blog/ArticleSourceForm.vue";
    import SearchField from "@/Components/SearchField.vue";
    import VirtualRemoteTable from "@/Components/VirtualRemoteTable.vue";

    import { formatDate } from "@/Utils/Common";

    const articleSourceFormRef = ref(null);
    const tableRef = ref(null);
    const search = ref(null);

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
            key: "ai_prompts_count",
            title: "Prompts de IA",
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

    const filters = computed(() => {
        if( !search.value ) return {};

        const { key, search: searchValue } = search.value;

        return {
            [key]: searchValue,
        };
    });

    function rowProps({ item }) {
        return {
            style: { cursor: "pointer" },
            onDblclick: () => articleSourceFormRef.value.open(item),
        };
    }

    function refresh() {
        tableRef.value?.fetchItems(1);
    }
</script>
