<template>
    <BottomSheet
        ref="bottomSheetRef"
        overlay-id="scraped-article-list"
        title="Posts Baixados"
        height="90dvh"
        :loading="listLoading"
        :actions="listSheetActions"
        v-model="isOpen"
    >
        <v-row>
            <v-col cols="12" lg="8">
                <SearchField
                    :fields="searchFields"
                    variant="tonal"
                    @refresh="handleSearch"
                    @update:model-value="handleSearch"
                    v-model="search"
                ></SearchField>
            </v-col>

            <v-col cols="12" lg="4">
                <v-card hover variant="tonal">
                    <v-select
                        label="Status"
                        variant="solo"
                        multiple
                        clearable
                        chips
                        closable-chips
                        :items="statusFilterOptions"
                        @click:clear="handleStatusFiltersClear"
                        @update:model-value="handleSearch"
                        v-model="statusFilters"
                    ></v-select>
                </v-card>
            </v-col>
        </v-row>

        <v-row class="mt-4">
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

        <SimplePagination
            v-if="pagination.total > 0"
            sticky
            class="mt-4"
            :disabled="listLoading"
            :length="pagination.lastPage"
            @update:model-value="handlePaginationUpdate"
            v-model="pagination.page"
        />
    </BottomSheet>

    <Dialog
        overlay-id="scraped-article-content"
        :title="selectedScrapedArticleTitle"
        no-body
        scrollable
        max-width="900px"
        :loading="contentLoading"
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
                        :disabled="contentLoading"
                        v-bind="props"
                    ></v-btn>
                </template>

                <v-list border slim>
                    <v-list-item
                        v-if="canStartTranslation"
                        prepend-icon="mdi-translate"
                        title="Traduzir"
                        class="text-primary"
                        :disabled="translating"
                        @click="startTranslation"
                    ></v-list-item>

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
        overlay-id="scraped-article-reject-translation"
        title="Rejeitar Tradução"
        max-width="500px"
        :loading="rejectLoading"
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
                :loading="rejectLoading"
                :disabled="rejectLoading"
                @click="rejectTranslation(false)"
            ></v-btn>

            <v-btn
                variant="tonal"
                color="error"
                prepend-icon="mdi-translate-off"
                text="Rejeitar Permanentemente"
                :loading="rejectLoading"
                :disabled="rejectLoading"
                @click="rejectTranslation(true)"
            ></v-btn>
        </template>
    </Dialog>

    <ScrapedArticleBulkDeleteDialog
        ref="bulkDeleteDialogRef"
        @deleted="handleBulkDeleted"
    />
</template>

<script setup>
    import { ref, reactive, computed, nextTick, onUnmounted, watch } from "vue";

    import api from "@/api";

    import BottomSheet from "@/Components/BottomSheet.vue";
    import Dialog from "@/Components/Dialog.vue";
    import SearchField from "@/Components/SearchField.vue";
    import SimplePagination from "@/Components/SimplePagination.vue";
    import ScrapedArticleBulkDeleteDialog from "@/Components/Admin/Blog/ScrapedArticleBulkDeleteDialog.vue";

    import { copyText, formatDate } from "@/Utils/Common";

    import {
        alertError,
        confirm,
        toastSuccess,
    } from "@/Utils/Alert";

    const bottomSheetRef = ref(null);

    const isOpen = ref(false);
    const isScrapedArticleContentDialogOpen = ref(false);
    const isRejectTranslationDialogOpen = ref(false);

    const bulkDeleteDialogRef = ref(null);

    const scrapedArticles = ref([]);
    const articleSource = ref(null);
    const search = ref(null);

    const DEFAULT_STATUS_FILTERS = ["active", "unpublished"];

    const statusFilters = ref([...DEFAULT_STATUS_FILTERS]);

    const statusFilterOptions = [
        { value: "active", title: "Ativos" },
        { value: "inactive", title: "Inativos" },
        { value: "published", title: "Publicados" },
        { value: "unpublished", title: "Não publicados" },
        { value: "translated", title: "Traduzidos" },
        { value: "untranslated", title: "Não traduzidos" },
        { value: "rejected", title: "Rejeitados" },
        { value: "not_rejected", title: "Não rejeitados" },
    ];

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
            key: "translated_title",
            title: "Título Traduzido",
        },
        {
            key: "url",
            title: "URL",
        },
    ]);

    const selectedScrapedArticle = ref(null);
    const contentTab = ref("original");

    const listLoading = ref(false);
    const contentLoading = ref(false);
    const rejectLoading = ref(false);
    const scraping = ref(false);
    const translating = ref(false);

    const TRANSLATION_POLL_INTERVAL = 3000;
    const TRANSLATION_POLL_MAX_ATTEMPTS = 200;

    let translationPollInterval = null;
    let translationPollAttempts = 0;
    let translationInitialAttempts = 0;

    const pagination = reactive({
        page: 1,
        perPage: 10,
        lastPage: 1,
        total: 0,
    });

    const listSheetActions = computed(() => [
        {
            key: "refresh",
            prependIcon: "mdi-refresh",
            text: "Atualizar",
            disabled: listLoading.value,
            onClick: () => handlePaginationUpdate(pagination.page),
        },
        {
            key: "bulk-delete",
            variant: "tonal",
            color: "error",
            prependIcon: "mdi-delete-sweep",
            text: "Excluir em Massa",
            disabled: listLoading.value,
            onClick: () => bulkDeleteDialogRef.value?.open(articleSource.value),
        },
        {
            key: "start-scraping",
            variant: "tonal",
            color: "primary",
            prependIcon: "mdi-cloud-download",
            text: "Iniciar Scraping",
            loading: scraping.value,
            onClick: startScraping,
        },
    ]);

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

    const canStartTranslation = computed(() => (
        !readOnly.value &&
        !hasTranslation.value &&
        !selectedScrapedArticle.value?.translation_rejected_at &&
        (selectedScrapedArticle.value?.translation_attempts ?? 0) < 3
    ));

    const selectedScrapedArticleTitle = computed(() => {
        const article = selectedScrapedArticle.value;

        if( !article ) return "";

        if( contentTab.value === "translated" ) {
            return article.translated_title || "";
        }

        return article.title || "";
    });

    async function handleBulkDeleted() {
        pagination.page = 1;
        await getScrapedArticles();
    }

    function buildSearchParams() {
        const params = {};

        if( search.value ) {
            const { key, search: searchValue } = search.value;
            params[key] = searchValue;
        }

        if( statusFilters.value.length > 0 ) {
            params.status_filters = statusFilters.value;
        }

        return params;
    }

    async function getScrapedArticles() {
        listLoading.value = true;

        try {
            const url = route("api.scraped-articles.index");

            const params = {
                page: pagination.page,
                per_page: pagination.perPage,
                ...buildSearchParams(),
            };

            if( articleSource.value?.id ) {
                params.article_source_id = articleSource.value.id;
            }

            const { data } = await api.get(url, { params });

            const mappedScrapedArticles = data.scraped_articles.map(sa => ({
                ...sa,
                finalTitle: sa.translated_title || sa.title || "",
            }));

            scrapedArticles.value = mappedScrapedArticles;

            pagination.page = data.current_page;
            pagination.perPage = data.per_page;
            pagination.total = data.total;
            pagination.lastPage = data.last_page;

            nextTick(() => {
                bottomSheetRef.value?.scrollToTop();
            });
        } catch (e) {
            console.error(e);
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar artigos");
        } finally {
            listLoading.value = false;
        }
    }

    async function getScrapedArticleById(id, { silent = false } = {}) {
        if( !silent ) contentLoading.value = true;

        try {
            const url = route("api.scraped-articles.show", id);

            const { data } = await api.get(url);

            selectedScrapedArticle.value = data.scraped_article;

            if( !silent ) {
                nextTick(() => {
                    const { translated_content } = selectedScrapedArticle.value;
                    contentTab.value = !!translated_content ? "translated" : "original";
                });
            }

            return data.scraped_article;
        } catch (e) {
            console.error(e);

            const { message } = e?.response?.data || {};

            alertError("Ooops", message || "Erro ao buscar artigo");

            if( !silent ) {
                isScrapedArticleContentDialogOpen.value = false;
            }

            return null;
        } finally {
            if( !silent ) contentLoading.value = false;
        }
    }

    async function disableScrapedArticle() {
        contentLoading.value = true;

        try {
            const url = route("api.scraped-articles.disable", selectedScrapedArticle.value.id);

            await api.patch(url);

            toastSuccess("Ok", "Artigo desativado com sucesso");

            isScrapedArticleContentDialogOpen.value = false;

            await getScrapedArticles();
        } catch (e) {
            console.error(e);
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao desativar artigo");
        } finally {
            contentLoading.value = false;
        }
    }

    async function rejectTranslation(permanent = false) {
        rejectLoading.value = true;

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

            await getScrapedArticles();
        } catch (e) {
            console.error(e);
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao rejeitar tradução");
        } finally {
            rejectLoading.value = false;
        }
    }

    async function resetTranslationAttempts() {
        contentLoading.value = true;

        try {
            const url = route(
                "api.scraped-articles.reset-translation-attempts",
                selectedScrapedArticle.value.id,
            );

            const { data } = await api.patch(url);

            selectedScrapedArticle.value = data.scraped_article;

            toastSuccess("Ok", data.message || "Contagem de tentativas reiniciada com sucesso");

            await getScrapedArticles();
        } catch (e) {
            console.error(e);
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao reiniciar tentativas de tradução");
        } finally {
            contentLoading.value = false;
        }
    }

    function stopTranslationPolling() {
        if( translationPollInterval ) {
            clearInterval(translationPollInterval);
            translationPollInterval = null;
        }

        translationPollAttempts = 0;
        translating.value = false;
    }

    async function pollTranslationStatus() {
        const article = selectedScrapedArticle.value;

        if( !article ) {
            stopTranslationPolling();
            return;
        }

        translationPollAttempts++;

        if( translationPollAttempts > TRANSLATION_POLL_MAX_ATTEMPTS ) {
            stopTranslationPolling();

            alertError(
                "Ooops",
                "A tradução está demorando mais que o esperado. Atualize o artigo manualmente.",
            );

            return;
        }

        const updatedArticle = await getScrapedArticleById(article.id, { silent: true });

        if( !updatedArticle ) {
            stopTranslationPolling();
            return;
        }

        if( updatedArticle.translated_content ) {
            stopTranslationPolling();

            contentTab.value = "translated";

            toastSuccess("Ok", "Tradução concluída com sucesso");

            await getScrapedArticles();

            return;
        }

        if( (updatedArticle.translation_attempts ?? 0) > translationInitialAttempts ) {
            stopTranslationPolling();

            alertError(
                "Ooops",
                "Não foi possível traduzir o artigo. Tente novamente mais tarde.",
            );
        }
    }

    function startTranslationPolling() {
        stopTranslationPolling();

        translating.value = true;

        translationInitialAttempts = selectedScrapedArticle.value?.translation_attempts ?? 0;
        translationPollAttempts = 0;

        translationPollInterval = setInterval(pollTranslationStatus, TRANSLATION_POLL_INTERVAL);
    }

    async function startTranslation() {
        if( !canStartTranslation.value || translating.value ) return;

        translating.value = true;

        try {
            const url = route(
                "api.scraped-articles.translate",
                selectedScrapedArticle.value.id,
            );

            await api.post(url);

            toastSuccess("Ok", "Tradução iniciada com sucesso");

            startTranslationPolling();
        } catch (e) {
            console.error(e);

            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao iniciar tradução");

            translating.value = false;
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

    function handleStatusFiltersClear() {
        nextTick(() => {
            statusFilters.value = [...DEFAULT_STATUS_FILTERS];
            handleSearch();
        });
    }

    function handleSearch() {
        pagination.page = 1;

        nextTick(async () => {
            await getScrapedArticles();
        });
    }

    function handleItemClick(scrapedArticle) {
        selectedScrapedArticle.value = scrapedArticle;

        isScrapedArticleContentDialogOpen.value = true;

        nextTick(async () => {
            await getScrapedArticleById(scrapedArticle.id);
        });
    }

    async function handleDisableScrapedArticle() {
        if( !selectedScrapedArticle.value.is_enabled ) return;

        const { isConfirmed } = await confirm(
            "Desativar Artigo",
            "Desativar o artigo?",
            { confirmButtonText: "Desativar" },
        );

        if( !isConfirmed ) return;

        await disableScrapedArticle();
    }

    function handleRejectTranslation() {
        if( !hasTranslation.value ) return;
        isRejectTranslationDialogOpen.value = true;
    }

    async function handleResetTranslationAttempts() {
        if( !canResetTranslationAttempts.value ) return;

        const { isConfirmed } = await confirm(
            "Reiniciar Tentativas de Tradução",
            "Reiniciar a contagem de tentativas de tradução deste artigo?",
            { confirmButtonText: "Reiniciar" },
        );

        if( !isConfirmed ) return;

        await resetTranslationAttempts();
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
        search.value = null;
        statusFilters.value = [...DEFAULT_STATUS_FILTERS];

        isOpen.value = true;

        nextTick(async () => {
            await getScrapedArticles();
        });
    }

    function close() {
        stopTranslationPolling();
        isScrapedArticleContentDialogOpen.value = false;
        isOpen.value = false;
    }

    onUnmounted(() => {
        stopTranslationPolling();
    });

    watch(isScrapedArticleContentDialogOpen, (isDialogOpen) => {
        if( !isDialogOpen ) stopTranslationPolling();
    });

    defineExpose({
        open,
        close,
        selectedScrapedArticle,
    });
</script>
