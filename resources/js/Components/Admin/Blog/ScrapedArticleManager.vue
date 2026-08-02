<template>
    <BottomSheet
        overlay-id="scraped-article-list"
        title="Posts Baixados"
        height="90dvh"
        :loading="loading"
        v-model="isOpen"
    >
        <v-row>
            <v-col
                v-for="(scrapedArticle, index) in scrapedArticles"
                :key="index"
                cols="12"
                xl="4" lg="6"
            >
                <v-card
                    border
                    hover
                    class="d-flex flex-column h-100"
                    variant="tonal"
                    :color="scrapedArticle.is_enabled ? null : 'error'"
                    @click="handleItemClick(scrapedArticle)"
                >
                    <v-card-title class="text-wrap">
                        <div class="lines-2">
                            {{ scrapedArticle.finalTitle }}
                        </div>
                    </v-card-title>

                    <v-card-text class="d-flex flex-column justify-end flex-grow-1 text-primary">
                        <div class="d-flex align-center justify-space-between ga-2">
                            {{ scrapedArticle.source.name }}

                            <div class="d-flex align-center ga-2">
                                <v-icon
                                    v-if="!!scrapedArticle.translated_title"
                                    icon="mdi-translate"
                                    color="orange"
                                    title="Tradução disponível"
                                ></v-icon>

                                <v-icon
                                    v-if="!!scrapedArticle.post_id"
                                    icon="mdi-newspaper"
                                    color="success"
                                    title="Post criado"
                                ></v-icon>
                            </div>
                        </div>

                        <div class="text-end mt-2">
                            {{ formatDate(scrapedArticle.created_at, "dd/MM/yyyy HH:mm") }}
                        </div>
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
                :loading="loading"
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
        overlay-id="scraped-article-content"
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
            <v-menu
                offset="8"
                location="bottom start"
            >
                <template #activator="{ props }">
                    <v-btn
                        variant="tonal"
                        prepend-icon="mdi-dots-vertical"
                        text="Mais Opções"
                        v-bind="props"
                    ></v-btn>
                </template>

                <v-list border slim>
                    <v-list-item
                        v-if="!readOnly"
                        prepend-icon="mdi-block-helper"
                        title="Desativar"
                        class="text-error"
                        @click="handleDisableScrapedArticle"
                    ></v-list-item>

                    <v-list-item
                        v-if="!readOnly && hasTranslation"
                        prepend-icon="mdi-translate-off"
                        title="Rejeitar Tradução"
                        class="text-warning"
                        @click="handleRejectTranslation"
                    ></v-list-item>

                    <v-list-item
                        v-if="canResetTranslationAttempts"
                        prepend-icon="mdi-restart"
                        title="Reiniciar Tentativas de Tradução"
                        class="text-info"
                        @click="handleResetTranslationAttempts"
                    ></v-list-item>

                    <v-list-item
                        prepend-icon="mdi-content-copy"
                        title="Copiar Texto da Aba"
                        @click="copyActiveTabText"
                    ></v-list-item>

                    <v-list-item
                        v-if="hasTranslation"
                        prepend-icon="mdi-content-copy"
                        title="Copiar Original e Traduzido"
                        @click="copyOriginalAndTranslated"
                    ></v-list-item>

                    <v-list-item
                        :href="selectedScrapedArticle?.url"
                        target="_blank"
                        prepend-icon="mdi-open-in-new"
                        title="Abrir Original"
                    ></v-list-item>
                </v-list>
            </v-menu>

            <v-spacer></v-spacer>

            <slot
                v-if="!readOnly"
                name="content-action-buttons"
            ></slot>
        </template>
    </Dialog>

    <Dialog
        overlay-id="scraped-article-disable"
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
        overlay-id="scraped-article-reject-translation"
        title="Rejeitar Tradução"
        max-width="500px"
        v-model="isRejectTranslationDialogOpen"
    >
        <p class="mb-2">
            A tradução será removida e o conteúdo original será exibido.
        </p>

        <p class="text-medium-emphasis text-body-2 mb-0">
            <strong>Rejeitar:</strong> o artigo voltará ao processo de tradução automaticamente.
        </p>

        <p class="text-medium-emphasis text-body-2 mb-0">
            <strong>Rejeitar permanentemente:</strong> o artigo nunca mais será traduzido.
        </p>

        <template #actions>
            <v-btn
                variant="tonal"
                color="warning"
                prepend-icon="mdi-translate-off"
                text="Rejeitar"
                @click="rejectTranslation(false)"
            ></v-btn>

            <v-btn
                variant="tonal"
                color="error"
                prepend-icon="mdi-translate-off"
                text="Rejeitar Permanentemente"
                @click="rejectTranslation(true)"
            ></v-btn>
        </template>
    </Dialog>

    <Dialog
        overlay-id="scraped-article-reset-translation-attempts"
        title="Reiniciar Tentativas de Tradução"
        message="Reiniciar a contagem de tentativas de tradução deste artigo?"
        max-width="400px"
        v-model="isResetTranslationAttemptsDialogOpen"
    >
        <template #actions>
            <v-btn
                variant="tonal"
                color="info"
                prepend-icon="mdi-restart"
                text="Reiniciar"
                @click="resetTranslationAttempts"
            ></v-btn>
        </template>
    </Dialog>
</template>

<script setup>
    import { ref, reactive, computed, nextTick } from "vue";

    import api from "@/api";

    import BottomSheet from "@/Components/BottomSheet.vue";
    import Dialog from "@/Components/Dialog.vue";

    import { copyText, formatDate } from "@/Utils/Common";

    import {
        alertError,
        toastSuccess,
    } from "@/Utils/Alert";

    const isOpen = ref(false);
    const isScrapedArticleContentDialogOpen = ref(false);
    const isDisableScrapedArticleDialogOpen = ref(false);
    const isRejectTranslationDialogOpen = ref(false);
    const isResetTranslationAttemptsDialogOpen = ref(false);

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

    const readOnly = computed(() => (
        !!selectedScrapedArticle.value?.post_id ||
        selectedScrapedArticle.value?.is_enabled === false
    ));

    const hasTranslation = computed(() => (
        !!selectedScrapedArticle.value?.translated_title ||
        !!selectedScrapedArticle.value?.translated_content
    ));

    const canResetTranslationAttempts = computed(() => (
        !readOnly.value &&
        (selectedScrapedArticle.value?.translation_attempts ?? 0) > 0
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

            nextTick(() => {
                const { translated_content } = selectedScrapedArticle.value;
                contentTab.value = !!translated_content ? "translated" : "original";
            });
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

    async function rejectTranslation(permanent = false) {
        loading.value = true;

        try {
            const url = route(
                "api.scraped-articles.reject-translation",
                selectedScrapedArticle.value.id,
            );
            const { data } = await api.patch(url, { permanent });

            selectedScrapedArticle.value = data.scraped_article;

            contentTab.value = "original";

            toastSuccess("Ok", data.message || "Tradução rejeitada com sucesso");

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

    async function resetTranslationAttempts() {
        loading.value = true;

        try {
            const url = route(
                "api.scraped-articles.reset-translation-attempts",
                selectedScrapedArticle.value.id,
            );

            const { data } = await api.patch(url);

            selectedScrapedArticle.value = data.scraped_article;

            toastSuccess("Ok", data.message || "Contagem de tentativas reiniciada com sucesso");

            isResetTranslationAttemptsDialogOpen.value = false;

            nextTick(async () => {
                await getScrapedArticles();
            });
        } catch (e) {
            console.error(e);
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao reiniciar tentativas de tradução");
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

    function handleResetTranslationAttempts() {
        if( !canResetTranslationAttempts.value ) return;
        isResetTranslationAttemptsDialogOpen.value = true;
    }

    function handleContentClick(e) {
        if( e.target.tagName === "A" ) {
            window.open(e.target.href, "_blank");
        }
    }

    async function copyActiveTabText() {
        const article = selectedScrapedArticle.value;

        if( !article ) return;

        const isTranslated = contentTab.value === "translated";

        const title = isTranslated
            ? (article.translated_title || article.title || "")
            : (article.title || "");

        const html = isTranslated
            ? (article.translated_content ?? "")
            : (article.content ?? "");

        const content = [title, html].filter(Boolean).join("\n\n");

        const { success, error } = await copyText(content);

        if( !success ) {
            alertError("Ooops", error || "Erro ao copiar texto");
        } else {
            toastSuccess("Ok", "Texto copiado com sucesso");
        }
    }

    async function copyOriginalAndTranslated() {
        const article = selectedScrapedArticle.value;

        if( !article || !hasTranslation.value ) return;

        const original = [
            article.title || "",
            article.content ?? "",
        ].filter(Boolean).join("\n\n");

        const translated = [
            article.translated_title || "",
            article.translated_content ?? "",
        ].filter(Boolean).join("\n\n");

        const content = [
            `ORIGINAL: ${original}`,
            "",
            `TRADUZIDO: ${translated}`,
        ].join("\n");

        const { success, error } = await copyText(content);

        if( !success ) {
            alertError("Ooops", error || "Erro ao copiar texto");
        } else {
            toastSuccess("Ok", "Texto copiado com sucesso");
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
