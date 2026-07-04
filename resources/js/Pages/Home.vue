<template>
    <Container title="Início" gapped>
        <section class="d-flex flex-column align-center text-center py-8">
            <v-img
                src="/images/logo.webp"
                :alt="appName"
                width="120"
                height="120"
                class="mb-4"
            ></v-img>

            <h1 class="text-primary mb-2">
                {{ appName }}
            </h1>

            <p class="text-grey mb-6" style="max-width: 480px;">
                Bem-vindo ao meu site. Explore artigos do blog e ferramentas práticas para o dia a dia.
            </p>

            <div class="d-flex flex-wrap ga-3 justify-center">
                <v-btn
                    color="primary"
                    prepend-icon="mdi-newspaper"
                    text="Ver o Blog"
                    @click.prevent="goTo(route('blog.index'))"
                ></v-btn>

                <v-btn
                    variant="tonal"
                    prepend-icon="mdi-tools"
                    text="Ferramentas"
                    @click.prevent="goTo(route('tools'))"
                ></v-btn>
            </div>
        </section>

        <section v-if="loadingPosts || posts.length > 0">
            <div class="d-flex align-center justify-space-between mb-4">
                <h2 class="text-h5 my-0">
                    Posts recentes
                </h2>

                <v-btn
                    variant="text"
                    color="primary"
                    append-icon="mdi-arrow-right"
                    text="Ver todos"
                    @click.prevent="goTo(route('blog.index'))"
                ></v-btn>
            </div>

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

        <section>
            <div class="d-flex align-center justify-space-between mb-4">
                <h2 class="text-h5 my-0">
                    Ferramentas
                </h2>

                <v-btn
                    variant="text"
                    color="primary"
                    append-icon="mdi-arrow-right"
                    text="Ver todas"
                    @click.prevent="goTo(route('tools'))"
                ></v-btn>
            </div>

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
