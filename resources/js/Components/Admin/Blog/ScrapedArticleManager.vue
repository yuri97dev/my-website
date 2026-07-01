<template>
    <BottomSheet
        title="Posts Baixados"
        height="90dvh"
        :loading="loading"
        v-model="isOpen"
    >
        <v-row>
            <template v-if="loading">
                <v-col
                    v-for="n in 4"
                    :key="n"
                    cols="12"
                    xl="4" lg="6"
                >
                    <v-card variant="tonal" class="bg-surface-light h-100">
                        <v-skeleton-loader
                            type="paragraph"
                            class="h-100"
                        ></v-skeleton-loader>
                    </v-card>
                </v-col>
            </template>

            <v-col
                v-for="(scrapedArticle, index) in scrapedArticles"
                :key="index"
                cols="12"
                xl="4" lg="6"
            >
                <v-card
                    border
                    hover
                    variant="tonal"
                    class="h-100"
                    @click="handleItemClick(scrapedArticle)"
                >
                    <v-card-title class="text-wrap">
                        <div class="lines-2">
                            {{ scrapedArticle.finalTitle }}
                        </div>
                    </v-card-title>

                    <v-card-text class="text-primary">
                        {{ scrapedArticle.source.name }}
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <div
            v-if="scrapedArticles.length > 0"
            class="d-flex justify-center mt-4"
        >
            <v-btn
                prepend-icon="mdi-arrow-down-circle"
                text="Carregar Mais"
                variant="tonal"
                color="success"
                :disabled="pagination.page === pagination.lastPage"
                @click="handlePaginationUpdate(pagination.page + 1)"
            ></v-btn>
        </div>

        <template #actions>
            <v-btn
                prepend-icon="mdi-refresh"
                text="Atualizar"
                :disabled="loading"
                @click="handlePaginationUpdate(1)"
            ></v-btn>

            <v-btn
                variant="tonal"
                color="primary"
                prepend-icon="mdi-cloud-download"
                text="Iniciar Scraping"
                :loading="scraping"
                @click="startScraping"
            ></v-btn>
        </template>
    </BottomSheet>

    <Dialog
        :title="selectedScrapedArticleTitle"
        no-body
        scrollable
        max-width="900px"
        v-model="isScrapedArticleContentDialogOpen"
    >
        <div>
            <v-tabs grow align-tabs="center" v-model="contentTab">
                <v-tab prepend-icon="mdi-web" text="Original" value="original"></v-tab>
                <v-tab prepend-icon="mdi-translate" text="Traduzido" value="translated" :disabled="!hasTranslation"></v-tab>
            </v-tabs>

            <v-divider></v-divider>
        </div>

        <v-card-text>
            <v-tabs-window v-model="contentTab">
                <v-tabs-window-item value="original">
                    <div
                        class="post-container"
                        @click.prevent="handleContentClick"
                        v-html="selectedScrapedArticle?.content ?? ''"
                    ></div>
                </v-tabs-window-item>

                <v-tabs-window-item value="translated">
                    <div
                        class="post-container"
                        @click.prevent="handleContentClick"
                        v-html="selectedScrapedArticle?.translated_content ?? ''"
                    ></div>
                </v-tabs-window-item>
            </v-tabs-window>
        </v-card-text>

        <template #actions>
            <v-btn
                v-if="selectedScrapedArticle?.is_enabled"
                variant="tonal"
                color="error"
                prepend-icon="mdi-block-helper"
                text="Desativar"
                @click="handleDisableScrapedArticle"
            ></v-btn>

            <v-btn
                v-if="hasTranslation"
                variant="tonal"
                color="warning"
                prepend-icon="mdi-translate-off"
                text="Rejeitar Tradução"
                @click="handleRejectTranslation"
            ></v-btn>

            <v-spacer></v-spacer>

            <slot name="content-action-buttons"></slot>

            <v-btn
                :href="selectedScrapedArticle?.url"
                target="_blank"
                prepend-icon="mdi-open-in-new"
                text="Abrir Original"
            ></v-btn>
        </template>
    </Dialog>

    <Dialog
        title="Desativar Artigo"
        message="Desativar o artigo?"
        max-width="400px"
        v-model="isDisableScrapedArticleDialogOpen"
    >
        <template #actions>
            <v-btn
                variant="tonal"
                color="error"
                prepend-icon="mdi-block-helper"
                text="Desativar"
                @click="disableScrapedArticle"
            ></v-btn>
        </template>
    </Dialog>

    <Dialog
        title="Rejeitar Tradução"
        message="Rejeitar a tradução e exibir o conteúdo original?"
        max-width="400px"
        v-model="isRejectTranslationDialogOpen"
    >
        <template #actions>
            <v-btn
                variant="tonal"
                color="warning"
                prepend-icon="mdi-translate-off"
                text="Rejeitar Tradução"
                @click="rejectTranslation"
            ></v-btn>
        </template>
    </Dialog>
</template>

<script setup>
    import { ref, reactive, computed, nextTick } from "vue";

    import api from "@/api";

    import BottomSheet from "@/Components/BottomSheet.vue";
    import Dialog from "@/Components/Dialog.vue";

    import {
        alertError,
        toastSuccess,
    } from "@/Utils/Alert";

    const isOpen = ref(false);
    const isScrapedArticleContentDialogOpen = ref(false);
    const isDisableScrapedArticleDialogOpen = ref(false);
    const isRejectTranslationDialogOpen = ref(false);

    const scrapedArticles = ref([]);
    const articleSource = ref(null);

    const selectedScrapedArticle = ref(null);
    const contentTab = ref("original");

    const loading = ref(false);
    const scraping = ref(false);

    const pagination = reactive({
        page: 1,
        perPage: 10,
        lastPage: 1,
        total: 0,
    });

    const hasTranslation = computed(() => (
        !!selectedScrapedArticle.value?.translated_title ||
        !!selectedScrapedArticle.value?.translated_content
    ));

    const selectedScrapedArticleTitle = computed(() => {
        const article = selectedScrapedArticle.value;

        if( !article ) return "";

        if( contentTab.value === "translated" ) {
            return article.translated_title || "";
        }

        return article.title || "";
    });

    async function getScrapedArticles() {
        loading.value = true;

        try {
            const url = route("api.scraped-articles.index");

            const params = {
                page: pagination.page,
                per_page: pagination.perPage,
            };

            if( articleSource.value?.id ) {
                params.article_source_id = articleSource.value.id;
            }

            const { data } = await api.get(url, { params });

            const mappedScrapedArticles = data.scraped_articles.map(sa => ({
                ...sa,
                finalTitle: sa.translated_title || sa.title || "",
            }));

            if( pagination.page === 1 ) {
                scrapedArticles.value = mappedScrapedArticles;
            } else {
                scrapedArticles.value.push(...mappedScrapedArticles);
            }

            pagination.page = data.current_page;
            pagination.perPage = data.per_page;
            pagination.total = data.total;
            pagination.lastPage = data.last_page;
        } catch (e) {
            console.error(e);
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar artigos");
        } finally {
            loading.value = false;
        }
    }

    async function getScrapedArticleById(id) {
        loading.value = true;

        try {
            const url = route("api.scraped-articles.show", id);
            const { data } = await api.get(url);

            selectedScrapedArticle.value = data.scraped_article;
        } catch (e) {
            console.error(e);
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar artigo");
            isScrapedArticleContentDialogOpen.value = false;
        } finally {
            loading.value = false;
        }
    }

    async function disableScrapedArticle() {
        loading.value = true;

        try {
            const url = route("api.scraped-articles.disable", selectedScrapedArticle.value.id);
            await api.patch(url);

            toastSuccess("Ok", "Artigo desativado com sucesso");

            isDisableScrapedArticleDialogOpen.value = false;
            isScrapedArticleContentDialogOpen.value = false;

            nextTick(async () => {
                await getScrapedArticles();
            });
        } catch (e) {
            console.error(e);
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao desativar artigo");
        } finally {
            loading.value = false;
        }
    }

    async function rejectTranslation() {
        loading.value = true;

        try {
            const url = route(
                "api.scraped-articles.reject-translation",
                selectedScrapedArticle.value.id,
            );
            const { data } = await api.patch(url);

            selectedScrapedArticle.value = data.scraped_article;

            contentTab.value = "original";

            toastSuccess("Ok", "Tradução rejeitada com sucesso");

            isRejectTranslationDialogOpen.value = false;

            nextTick(async () => {
                await getScrapedArticles();
            });
        } catch (e) {
            console.error(e);
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao rejeitar tradução");
        } finally {
            loading.value = false;
        }
    }

    async function startScraping() {
        scraping.value = true;

        try {
            const url = route("api.scraped-articles.scrape");
            const payload = {};

            if( articleSource.value?.id ) {
                payload.article_source_id = articleSource.value.id;
            }

            await api.post(url, payload);

            toastSuccess("Ok", "Scraping iniciado com sucesso");
        } catch (e) {
            console.error(e);
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao iniciar scraping");
        } finally {
            scraping.value = false;
        }
    }

    async function handlePaginationUpdate(page) {
        pagination.page = page;
        await getScrapedArticles();
    }

    function handleItemClick(scrapedArticle) {
        contentTab.value = scrapedArticle.translated_content ? "translated" : "original";

        selectedScrapedArticle.value = scrapedArticle;

        isScrapedArticleContentDialogOpen.value = true;

        nextTick(async () => {
            await getScrapedArticleById(scrapedArticle.id);
        });
    }

    function handleDisableScrapedArticle() {
        if( !selectedScrapedArticle.value.is_enabled ) return;
        isDisableScrapedArticleDialogOpen.value = true;
    }

    function handleRejectTranslation() {
        if( !hasTranslation.value ) return;
        isRejectTranslationDialogOpen.value = true;
    }

    function handleContentClick(e) {
        if( e.target.tagName === "A" ) {
            window.open(e.target.href, "_blank");
        }
    }

    function open(data = {}) {
        articleSource.value = data;
        pagination.page = 1;

        isOpen.value = true;

        nextTick(async () => {
            await getScrapedArticles();
        });
    }

    function close() {
        isScrapedArticleContentDialogOpen.value = false;
        isOpen.value = false;
    }

    defineExpose({
        open,
        close,
        selectedScrapedArticle,
    });
</script>
