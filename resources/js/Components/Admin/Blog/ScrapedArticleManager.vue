<template>
    <BottomSheet
        title="Posts Baixados"
        hide-footer
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
                    <v-skeleton-loader
                        type="card"
                    ></v-skeleton-loader>
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
                    @click="handleItemClick(scrapedArticle)"
                >
                    <v-card-title class="text-wrap">
                        <div class="lines-2">
                            {{ scrapedArticle.title }}
                        </div>
                    </v-card-title>

                    <v-card-text class="text-primary">
                        {{ scrapedArticle.source.name }}
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </BottomSheet>

    <Dialog
        :title="selectedScrapedArticle?.title ?? ''"
        scrollable
        max-width="900px"
        no-body
        v-model="isScrapedArticleContentDialogOpen"
    >
        <v-card-text>
            <div
                @click.prevent="handleContentClick"
                v-html="selectedScrapedArticle?.content ?? ''"
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
    import { ref, nextTick } from "vue";

    import api from "@/api";

    import BottomSheet from "@/Components/BottomSheet.vue";
    import Dialog from "@/Components/Dialog.vue";

    import {
        alertError,
        alertSuccess,
    } from "@/Utils/Alert";

    const isOpen = ref(false);
    const isScrapedArticleContentDialogOpen = ref(false);
    const isDisableScrapedArticleDialogOpen = ref(false);

    const scrapedArticles = ref([]);
    const articleSource = ref(null);

    const selectedScrapedArticle = ref(null);

    const loading = ref(false);

    async function getScrapedArticles() {
        loading.value = true;

        try {
            const url = route("api.scraped-articles.index");
            const { data } = await api.get(url);

            scrapedArticles.value = data.scraped_articles;
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

            alertSuccess("Ok", "Artigo desativado com sucesso");

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

    function handleItemClick(scrapedArticle) {
        selectedScrapedArticle.value = {
            content: "",
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
