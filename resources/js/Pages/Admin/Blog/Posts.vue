<template>
    <Container title="Posts" gapped>
        <SearchField
            :fields="searchFields"
            @refresh="refresh"
            v-model="search"
        ></SearchField>

        <VirtualRemoteTable
            ref="tableRef"
            items-key="posts"
            :href="route('api.blog.posts.index')"
            :headers="headers"
            :filters="filters"
            :row-props="rowProps"
        >
            <template #item.views_count="{ item }">
                {{ formatViewsCount(item.views_count) }}
            </template>

            <template #item.created_at="{ item }">
                <span v-if="!!item.created_at">
                    {{ formatDate(item.created_at, "dd/MM/yyyy HH:mm") }}
                </span>
            </template>

            <template #item.published_at="{ item }">
                <span v-if="!!item.published_at">
                    {{ formatDate(item.published_at, "dd/MM/yyyy HH:mm") }}
                </span>
            </template>

            <template #item.updated_at="{ item }">
                <span v-if="!!item.updated_at">
                    {{ formatDate(item.updated_at, "dd/MM/yyyy HH:mm") }}
                </span>
            </template>

            <template #item.deleted_at="{ item }">
                <span v-if="!!item.deleted_at">
                    {{ formatDate(item.deleted_at, "dd/MM/yyyy HH:mm") }}
                </span>
            </template>
        </VirtualRemoteTable>

        <PostForm
            ref="postFormRef"
            @form-closed="refresh"
        ></PostForm>

        <v-fab
            app
            icon="mdi-plus"
            size="large"
            color="primary"
            @click="postFormRef.open()"
        ></v-fab>
    </Container>
</template>

<script setup>
    import { ref, computed, onMounted } from "vue";

    import Container from "@/Components/Container.vue";
    import PostForm from "@/Components/Admin/Blog/PostForm.vue";
    import SearchField from "@/Components/SearchField.vue";
    import VirtualRemoteTable from "@/Components/VirtualRemoteTable.vue";

    import { formatDate } from "@/Utils/Common";

    const CELL_PROPS = {
        class: "text-no-wrap",
    };

    const postFormRef = ref(null);
    const tableRef = ref(null);
    const search = ref(null);

    const searchFields = ref([
        {
            key: "id",
            title: "ID",
        },
        {
            key: "title",
            title: "Título",
        },
        {
            key: "slug",
            title: "Slug",
        },
    ]);

    const headers = ref([
        {
            key: "id",
            title: "ID",
        },
        {
            key: "title",
            title: "Título",
            cellProps: {
                style: "min-width: 40ch;",
            },
        },
        {
            key: "category.name",
            title: "Categoria",
        },
        {
            key: "slug",
            title: "Slug",
            cellProps: {
                style: "min-width: 40ch;",
            },
        },
        {
            key: "views_count",
            title: "Visualizações",
            headerProps: CELL_PROPS,
            cellProps: CELL_PROPS,
        },
        {
            key: "created_at",
            title: "Criado Em",
            headerProps: CELL_PROPS,
            cellProps: CELL_PROPS,
        },
        {
            key: "published_at",
            title: "Publicado Em",
            headerProps: CELL_PROPS,
            cellProps: CELL_PROPS,
        },
        {
            key: "updated_at",
            title: "Atualizado Em",
            headerProps: CELL_PROPS,
            cellProps: CELL_PROPS,
        },
        {
            key: "deleted_at",
            title: "Deletado Em",
            headerProps: CELL_PROPS,
            cellProps: CELL_PROPS,
        },
    ]);

    const filters = computed(() => {
        if( !search.value ) return {};

        const { key, search: searchValue } = search.value;

        return {
            [key]: searchValue,
        };
    });

    function formatViewsCount(value) {
        return Number(value || 0).toLocaleString("pt-BR");
    }

    function rowProps({ item }) {
        return {
            style: { cursor: "pointer" },
            onDblclick: () => postFormRef.value.open(item),
        };
    }

    function refresh() {
        tableRef.value?.fetchItems(1);
    }

    onMounted(() => {
        const { id } = route().params;

        if( !id ) return;

        postFormRef.value.open({ id });
    });
</script>
