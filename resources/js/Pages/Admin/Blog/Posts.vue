<template>
    <Container title="Posts" gapped>
        <SearchField
            :fields="searchFields"
            @refresh="getPosts"
            @update:model-value="handleSearch"
            v-model="search"
        ></SearchField>

        <v-card>
            <v-data-table-server
                :headers="headers"
                :items="posts"
                :items-length="pagination.total"
                :loading="loading"
                @update:options="getPosts"
                @click:row="handleRowClick"
                v-model:page="pagination.page"
                v-model:items-per-page="pagination.perPage"
            >
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
            </v-data-table-server>
        </v-card>

        <PostForm
            ref="postFormRef"
            @form-closed="getPosts"
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
    import { ref, reactive, nextTick } from "vue";

    import api from "@/api";

    import Container from "@/Components/Container.vue";
    import PostForm from "@/Components/Admin/Blog/PostForm.vue";
    import SearchField from "@/Components/SearchField.vue";

    import { alertError } from "@/Utils/Alert";
    import { formatDate } from "@/Utils/Common";

    const CELL_PROPS = {
        class: "text-no-wrap",
    };

    const postFormRef = ref(null);

    const search = ref(null);
    const posts = ref([]);
    const loading = ref(false);

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

    const pagination = reactive({
        page: 1,
        perPage: 10,
        total: 0,
    });

    async function getPosts() {
        loading.value = true;

        try {
            const url = route("api.blog.posts.index");

            const params = {
                page: pagination.page,
                per_page: pagination.perPage,
            };

            if( search.value ) {
                const { key, search: searchValue } = search.value || {};
                params[key] = searchValue;
            }

            const { data } = await api.get(url, { params });

            posts.value = data.posts;

            pagination.page = data.current_page;
            pagination.perPage = data.per_page;
            pagination.total = data.total;
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar posts");
        }

        loading.value = false;
    }

    function handleSearch() {
        pagination.page = 1;

        nextTick(async () => {
            await getPosts();
        });
    }

    function handleRowClick(e, { item }) {
        postFormRef.value.open(item);
    }
</script>
