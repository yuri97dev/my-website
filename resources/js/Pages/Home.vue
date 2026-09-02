<template>
    <Container title="Início" gapped>
        <v-spacer></v-spacer>

        <section v-if="loadingPosts || posts.length > 0">
            <div class="d-flex align-center justify-space-between">
                <h2 class="d-flex align-center ga-4 ma-0">
                    <v-icon icon="mdi-newspaper" size="24"></v-icon>
                    Posts Recentes
                </h2>

                <v-btn
                    variant="tonal"
                    color="primary"
                    append-icon="mdi-arrow-right"
                    text="Ver Todos"
                    @click.prevent="goTo(route('blog.index'))"
                ></v-btn>
            </div>

            <v-divider class="my-4"></v-divider>

            <v-row>
                <template v-if="loadingPosts">
                    <v-col
                        v-for="n in 3"
                        :key="n"
                        cols="12"
                        lg="6"
                        xl="4"
                    >
                        <v-card border class="h-100">
                            <v-img
                                height="240px"
                                class="bg-surface-light"
                                cover
                            ></v-img>

                            <v-skeleton-loader type="paragraph"></v-skeleton-loader>
                        </v-card>
                    </v-col>
                </template>

                <v-col
                    v-for="post in posts"
                    :key="post.id"
                    cols="12"
                    lg="6"
                    xl="4"
                >
                    <PostCard :post="post" />
                </v-col>
            </v-row>
        </section>

        <v-spacer></v-spacer>

        <section>
            <div class="d-flex align-center justify-space-between">
                <h2 class="d-flex align-center ga-4 ma-0">
                    <v-icon icon="mdi-tools" size="24"></v-icon>
                    Ferramentas
                </h2>

                <v-btn
                    variant="tonal"
                    color="primary"
                    append-icon="mdi-arrow-right"
                    text="Ver Todas"
                    @click.prevent="goTo(route('tools'))"
                ></v-btn>
            </div>

            <v-divider class="my-4"></v-divider>

            <v-row>
                <v-col
                    v-for="tool in tools"
                    :key="tool.key"
                    cols="12"
                    lg="6"
                    xl="4"
                >
                    <ToolCard :tool="tool" />
                </v-col>
            </v-row>
        </section>

        <v-spacer></v-spacer>
    </Container>
</template>

<script setup>
    import { ref, computed, onMounted } from "vue";
    import { usePage } from "@inertiajs/vue3";

    import Container from "@/Components/Container.vue";
    import PostCard from "@/Components/PostCard.vue";
    import ToolCard from "@/Components/ToolCard.vue";

    import api from "@/api";

    import { alertError } from "@/Utils/Alert";
    import { goTo } from "@/Utils/Common";
    import { tools } from "@/Data/tools";

    const page = usePage();

    const posts = ref([]);
    const loadingPosts = ref(false);

    const appName = computed(() => page.props.appName);

    async function getPosts() {
        loadingPosts.value = true;

        try {
            const url = route("api.blog.posts.index");

            const { data } = await api.get(url, {
                params: { per_page: 3 },
            });

            posts.value = data.posts.map(post => {
                post.href = route("blog.show", {
                    category: post.category.slug,
                    slug: post.slug,
                });

                return post;
            });
        } catch (e) {
            console.log(e);
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar posts");
        } finally {
            loadingPosts.value = false;
        }
    }

    onMounted(async () => {
        await getPosts();
    });
</script>
