<template>
    <Container title="Blog" gapped>
        <SearchField
            :fields="searchFields"
            multiple
            @refresh="getPosts"
            @update:model-value="handleSearch"
            v-model="search"
        ></SearchField>

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
                                height="240px"
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
                    <v-card
                        hover
                        border
                        class="h-100"
                        :href="post.href"
                        @click.prevent="goToPostDetail(post)"
                    >
                        <v-img
                            :src="post.thumbnail_path"
                            height="240px"
                            class="bg-surface-light"
                            cover
                        ></v-img>

                        <v-card-title class="text-primary">
                            <div class="lines-2">
                                {{ post.title }}
                            </div>
                        </v-card-title>

                        <v-card-text :title="post.excerpt">
                            <div class="lines-2">
                                {{ post.excerpt || "Sem resumo disponível" }}
                            </div>

                            <div class="d-flex align-center justify-space-between text-grey mt-2">
                                <v-chip color="primary" variant="outlined">
                                    {{ post.category.name }}
                                </v-chip>

                                <div class="d-flex align-center ga-2 text-grey">
                                    <v-icon size="small" icon="mdi-clock"></v-icon>
                                    {{ formatDate(post.updated_at, "dd/MM/yyyy HH:mm") }}
                                </div>
                            </div>
                        </v-card-text>
                    </v-card>
                </v-col>
            </v-row>
        </div>
    </Container>
</template>

<script setup>
    import { ref, reactive, computed, onMounted, nextTick } from "vue";

    import Container from "@/Components/Container.vue";
    import SearchField from "@/Components/SearchField.vue";

    import api from "@/api";

    import { alertError } from "@/Utils/Alert";
    import { goTo, formatDate } from "@/Utils/Common";

    const props = defineProps({
        category: {
            type: String,
            required: false,
        },
    });

    const posts = ref([]);
    const loading = ref(false);

    const search = ref([]);

    const searchFields = ref([
        {
            key: "title",
            title: "Título",
        },
        {
            key: "category",
            title: "Categoria",
        },
    ]);

    const pagination = reactive({
        page: 1,
        perPage: 50,
        total: 0,
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

            posts.value = data.posts.map(post => {
                post.href = route("blog.show", {
                    category: post.category.slug,
                    slug: post.slug,
                });

                return post;
            });

            pagination.page = data.current_page;
            pagination.perPage = data.per_page;
            pagination.total = data.total;
        } catch (e) {
            console.log(e);
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar posts");
        } finally {
            loading.value = false;
        }
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

    function goToPostDetail(post) {
        goTo(post.href);
    }

    onMounted(async () => {
        await getPosts();
    });
</script>
