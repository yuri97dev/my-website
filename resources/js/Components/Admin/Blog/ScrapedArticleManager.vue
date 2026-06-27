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

        <template #actions>
            <div class="text-center w-100">
                <v-pagination
                    :total-visible="5"
                    :length="pagination.lastPage"
                    :disabled="loading"
                    @update:model-value="handlePaginationUpdate"
                ></v-pagination>
            </div>
        </template>
    </BottomSheet>

    <Dialog
        :title="selectedScrapedArticle?.finalTitle ?? ''"
        scrollable
        max-width="900px"
        no-body
        v-model="isScrapedArticleContentDialogOpen"
    >
        <v-card-text>
            <div
                class="post-container"
                @click.prevent="handleContentClick"
                v-html="selectedScrapedArticle?.finalContent ?? ''"
            ></div>
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
</template>

<script setup>
    import { ref, reactive, nextTick } from "vue";

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

    const scrapedArticles = ref([]);
    const articleSource = ref(null);

    const selectedScrapedArticle = ref(null);

    const loading = ref(false);

    const pagination = reactive({
        page: 1,
        perPage: 10,
        lastPage: 1,
        total: 0,
    });

    async function getScrapedArticles() {
        loading.value = true;

        try {
            const url = route("api.scraped-articles.index");

            const params = {
                page: pagination.page,
                per_page: pagination.perPage,
            };

            const { data } = await api.get(url, { params });

            scrapedArticles.value = data.scraped_articles.map(sa => ({
                ...sa,
                finalTitle: sa.translated_title || sa.title || "",
                finalContent: sa.translated_content || sa.content || "",
            }));

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

            selectedScrapedArticle.value = {
                ...data.scraped_article,
                finalTitle: (
                    data.scraped_article.translated_title ||
                    data.scraped_article.title ||
                    ""
                ),
                finalContent: (
                    data.scraped_article.translated_content ||
                    data.scraped_article.content ||
                    ""
                ),
            };
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

    async function handlePaginationUpdate(page) {
        pagination.page = page;
        await getScrapedArticles();
    }

    function handleItemClick(scrapedArticle) {
        selectedScrapedArticle.value = {
            finalTitle: scrapedArticle.finalTitle,
            finalContent: scrapedArticle.finalContent,
        };

        isScrapedArticleContentDialogOpen.value = true;

        nextTick(async () => {
            await getScrapedArticleById(scrapedArticle.id);
        });
    }

    function handleDisableScrapedArticle() {
        if( !selectedScrapedArticle.value.is_enabled ) return;
        isDisableScrapedArticleDialogOpen.value = true;
    }

    function handleContentClick(e) {
        if( e.target.tagName === "A" ) {
            window.open(e.target.href, "_blank");
        }
    }

    function open(data = {}) {
        articleSource.value = data;
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
