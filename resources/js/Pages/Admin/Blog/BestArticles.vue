<template>
    <Container title="Melhores Artigos" gapped>
        <div v-if="error">
            <v-alert type="error" variant="tonal" :text="error" role="alert" />
        </div>

        <div v-if="loading && !articles.length">
            <v-row>
                <v-col v-for="n in 6" :key="n" cols="12" lg="6" xl="4">
                    <v-skeleton-loader type="article" />
                </v-col>
            </v-row>
        </div>

        <div
            v-else-if="!error && !articles.length"
            class="d-flex align-center justify-center flex-grow-1"
        >
            <v-empty-state
                icon="mdi-file-search-outline"
                title="Nenhum artigo selecionado"
                text="Não há candidatos elegíveis ou nenhum artigo foi aprovado pelo filtro."
            ></v-empty-state>
        </div>

        <div v-else-if="articles.length">
            <v-row>
                <v-col v-for="(article, index) in articles" :key="article.id" cols="12" lg="6" xl="4">
                    <v-card border class="h-100 d-flex flex-column">
                        <v-card-text class="pb-0">
                            <v-chip color="primary" variant="tonal">#{{ index + 1 }}</v-chip>
                        </v-card-text>

                        <v-card-title class="text-primary text-wrap">{{ article.title }}</v-card-title>

                        <v-card-text class="flex-grow-1">{{ article.excerpt || "Sem resumo disponível" }}</v-card-text>

                        <v-card-actions>
                            <v-btn v-if="safeUrl(article.url)" :href="safeUrl(article.url)" target="_blank"
                                rel="noopener noreferrer" prepend-icon="mdi-open-in-new" text="Ler original" />
                        </v-card-actions>
                    </v-card>
                </v-col>
            </v-row>
        </div>

        <v-fab
            app
            icon="mdi-refresh"
            size="large"
            color="primary"
            aria-label="Atualizar seleção"
            title="Atualizar seleção"
            :loading="loading"
            :disabled="loading"
            @click="refresh"
        ></v-fab>
    </Container>
</template>

<script setup>
    import { ref, onMounted, onBeforeUnmount } from "vue";

    import Container from "@/Components/Container.vue";

    import api from "@/api";

    const articles = ref([]);
    const loading = ref(false);
    const error = ref("");

    let controller;

    function safeUrl(value) {
        try {
            const url = new URL(value);
            return ["http:", "https:"].includes(url.protocol) ? url.href : null;
        } catch {
            return null;
        }
    }

    async function refresh() {
        if( loading.value ) return;

        loading.value = true;
        error.value = "";

        controller = new AbortController();

        try {
            const { data } = await api.get(route("api.blog.article-shortlist"), { signal: controller.signal });
            articles.value = data.data;
        } catch(e) {
            if( e.code !== "ERR_CANCELED" ) {
                error.value = e.response?.status === 429
                    ? "Muitas atualizações. Aguarde um minuto e tente novamente."
                    : e.response?.data?.message || "Não foi possível carregar a seleção. Tente novamente.";
            }
        } finally {
            loading.value = false;
        }
    }

    onMounted(refresh);
    onBeforeUnmount(() => controller?.abort());
</script>
