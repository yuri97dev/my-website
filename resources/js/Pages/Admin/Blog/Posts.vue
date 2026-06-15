<template>
    <Container title="Posts" gapped>
        <v-card hover border>
            <v-text-field
                type="search"
                placeholder="Pesquisar"
                prepend-inner-icon="mdi-magnify"
                append-inner-icon="mdi-refresh"
                variant="solo"
                clearable
                @keyup.enter="handleSearch"
                @click:append-inner="getPosts"
                v-model="search"
            ></v-text-field>
        </v-card>

        <v-card>
            <v-data-table-server
                :headers="headers"
                :items="posts"
                :items-length="pagination.total"
                :loading="loading"
                class="text-no-wrap"
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
    import { ref, reactive } from "vue";

    import api from "@/api";

    import Container from "@/Components/Container.vue";
    import PostForm from "@/Components/Admin/Blog/PostForm.vue";

    import { alertError } from "@/Utils/Alert";
    import { formatDate } from "@/Utils/Common";

    const postFormRef = ref(null);

    const search = ref("");
    const posts = ref([]);
    const loading = ref(false);

    const headers = ref([
        {
            key: "id",
            title: "ID",
        },
        {
            key: "title",
            title: "Título",
        },
        {
            key: "category.name",
            title: "Categoria",
        },
        {
            key: "slug",
            title: "Slug",
        },
        {
            key: "created_at",
            title: "Criado Em",
        },
        {
            key: "published_at",
            title: "Publicado Em",
        },
        {
            key: "updated_at",
            title: "Atualizado Em",
        },
        {
            key: "deleted_at",
            title: "Deletado Em",
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

    async function handleSearch() {
        pagination.page = 1;
        await getPosts();
    }

    function handleRowClick(e, { item }) {
        postFormRef.value.open(item);
    }
</script>
