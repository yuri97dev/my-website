<template>
    <Container title="Blog" gapped>
        <SearchField
            :fields="searchFields"
            multiple
            @refresh="handleSearch"
            @update:model-value="handleSearch"
            v-model="search"
        ></SearchField>

        <h2 v-if="!!category" class="text-grey my-0">
            Categoria:

            <strong class="text-primary text-decoration-underline">
                {{ category }}
            </strong>

            <v-icon
                role="button"
                size="small"
                icon="mdi-close"
                class="ms-2"
                title="Limpar filtro"
                @click="goTo(route('blog.index'))"
            ></v-icon>
        </h2>

        <div
            v-if="!loading && posts.length === 0"
            class="d-flex align-center justify-center flex-grow-1"
        >
            <v-empty-state
                icon="mdi-magnify"
                title="Nenhum resultado encontrado"
                :text="emptyStateText"
            ></v-empty-state>
        </div>

        <div v-else>
            <v-row>
                <template v-if="loading">
                    <v-col
                        v-for="n in 3"
                        :key="n"
                        cols="12"
                        lg="6" xl="4"
                    >
                        <v-card border class="h-100">
                            <v-img
                                height="var(--thumbnail-height)"
                                class="bg-surface-light"
                                cover
                            ></v-img>

                            <v-skeleton-loader
                                type="paragraph"
                            ></v-skeleton-loader>
                        </v-card>
                    </v-col>
                </template>

                <v-col
                    v-for="post in posts"
                    :key="post.id"
                    cols="12"
                    lg="6" xl="4"
                >
                    <PostCard :post="post" />
                </v-col>
            </v-row>

            <div
                v-if="pagination.page < pagination.lastPage"
                class="d-flex justify-center mt-4"
            >
                <v-btn
                    size="large"
                    prepend-icon="mdi-arrow-down-circle"
                    text="Carregar Mais"
                    variant="tonal"
                    color="success"
                    :loading="loading"
                    @click="handleLoadMore"
                ></v-btn>
            </div>
        </div>
    </Container>
</template>

<script setup>
    import { ref, reactive, computed, onMounted, nextTick } from "vue";

    import Container from "@/Components/Container.vue";
    import PostCard from "@/Components/PostCard.vue";
    import SearchField from "@/Components/SearchField.vue";

    import api from "@/api";

    import { alertError } from "@/Utils/Alert";
    import { goTo } from "@/Utils/Common";

    const props = defineProps({
        category: {
            type: String,
            required: false,
        },
    });

    const posts = ref([]);
    const loading = ref(false);

    const search = ref([]);

    const pagination = reactive({
        page: 1,
        perPage: 50,
        lastPage: 1,
        total: 0,
    });

    const searchFields = computed(() => {
        const fields = [{
            key: "title",
            title: "Título",
        }];

        if( !props.category ) fields.push({
            key: "category",
            title: "Categoria",
        });

        return fields;
    });

    const emptyStateText = computed(() => {
        if( search.value?.length == 0 ) {
            return "Ainda não há itens para exibir.";
        }

        if( props.category ) {
            return "Ainda não há itens para exibir nesta categoria.";
        }

        return "Tente ajustar a pesquisa ou os filtros para ampliar os resultados.";
    });

    async function getPosts() {
        loading.value = true;

        try {
            const url = route("api.blog.posts.index");

            const params = {
                page: pagination.page,
                per_page: pagination.perPage,
                category: props.category,
            };

            if( search.value?.length > 0 ) {
                search.value.forEach(item => {
                    const { key, search: searchValue } = item || {};
                    params[key] = searchValue;
                });
            }

            const { data } = await api.get(url, { params });

            const newPosts = data.posts.map(post => {
                post.href = route("blog.show", {
                    category: post.category.slug,
                    slug: post.slug,
                });

                return post;
            });

            if( pagination.page === 1 ) {
                posts.value = newPosts;
            } else {
                posts.value.push(...newPosts);
            }

            pagination.page = data.current_page;
            pagination.perPage = data.per_page;
            pagination.lastPage = data.last_page;
            pagination.total = data.total;
        } catch (e) {
            console.log(e);
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar posts");
        } finally {
            loading.value = false;
        }
    }

    async function handleLoadMore() {
        pagination.page++;
        await getPosts();
    }

    function handleSearch() {
        pagination.page = 1;

        window.scrollTo({
            top: 0,
            behavior: "smooth",
        });

        nextTick(async () => {
            await getPosts();
        });
    }

    onMounted(async () => {
        await getPosts();
    });
</script>
