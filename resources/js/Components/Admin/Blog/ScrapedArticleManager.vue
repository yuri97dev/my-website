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
                                    v-if="scrapedArticle.assistant_processing"
                                    icon="mdi-robot-outline"
                                    color="deep-purple"
                                    title="Em processamento pelo assistente"
                                ></v-icon>

                                <v-icon
                                    v-else-if="scrapedArticle.assistant_processed"
                                    icon="mdi-robot"
                                    color="deep-purple"
                                    title="Processado pelo assistente"
                                ></v-icon>

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

    <ScrapedArticleContentDialog
        ref="contentDialogRef"
        @updated="getScrapedArticles"
    >
        <template #action-buttons>
            <slot name="content-action-buttons"></slot>
        </template>
    </ScrapedArticleContentDialog>

    <ScrapedArticleBulkDeleteDialog
        ref="bulkDeleteDialogRef"
        @deleted="handleBulkDeleted"
    />
</template>

<script setup>
    import { ref, reactive, computed, nextTick } from "vue";

    import api from "@/api";

    import BottomSheet from "@/Components/BottomSheet.vue";
    import SearchField from "@/Components/SearchField.vue";
    import SimplePagination from "@/Components/SimplePagination.vue";
    import ScrapedArticleBulkDeleteDialog from "@/Components/Admin/Blog/ScrapedArticleBulkDeleteDialog.vue";
    import ScrapedArticleContentDialog from "@/Components/Admin/Blog/ScrapedArticleContentDialog.vue";

    import { formatDate } from "@/Utils/Common";

    import {
        alertError,
        toastSuccess,
    } from "@/Utils/Alert";

    const bottomSheetRef = ref(null);
    const contentDialogRef = ref(null);

    const isOpen = ref(false);

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
        { value: "assistant_processing", title: "Em processamento pelo assistente" },
        { value: "assistant_processed", title: "Processados pelo assistente" },
        { value: "not_assistant_processed", title: "Não processados pelo assistente" },
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

    const listLoading = ref(false);
    const scraping = ref(false);
    const assistantRunning = ref(false);

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
            key: "run-assistant",
            variant: "tonal",
            color: "deep-purple-lighten-2",
            prependIcon: "mdi-robot",
            text: "Acionar Assistente",
            loading: assistantRunning.value,
            onClick: runAssistant,
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
                assistant_processing: sa.processing_by !== null && Number(sa.processing_by) === 0,
                assistant_processed: sa.post?.author_id === data.assistant_author_id,
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

    async function startScraping() {
        scraping.value = true;

        try {
            const url = route("api.scraped-articles.scrape");
            const payload = {};

            if( articleSource.value?.id ) {
                payload.article_source_id = articleSource.value.id;
            }

            await api.post(url, payload);

            toastSuccess("Scraping iniciado com sucesso");
        } catch (e) {
            console.error(e);
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao iniciar scraping");
        } finally {
            scraping.value = false;
        }
    }

    async function runAssistant() {
        assistantRunning.value = true;

        try {
            const url = route("api.scraped-articles.run-assistant");

            await api.post(url);

            toastSuccess("Assistente iniciado com sucesso");
        } catch (e) {
            console.error(e);
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao acionar assistente");
        } finally {
            assistantRunning.value = false;
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
        contentDialogRef.value?.open(scrapedArticle);
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
        contentDialogRef.value?.close();
        isOpen.value = false;
    }

    defineExpose({
        open,
        close,
        get selectedScrapedArticle() {
            const article = contentDialogRef.value?.selectedScrapedArticle;

            return article?.value ?? article ?? null;
        },
    });
</script>
