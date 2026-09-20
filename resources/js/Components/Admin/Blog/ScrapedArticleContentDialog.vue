<template>
    <Dialog
        overlay-id="scraped-article-content"
        :title="dialogTitle"
        no-body
        scrollable
        max-width="900px"
        :loading="contentLoading"
        v-model="isOpen"
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
                        v-if="hasSelectionPrompt"
                        prepend-icon="mdi-robot-outline"
                        title="Ver Prompt do Assistente"
                        @click="isSelectionPromptDialogOpen = true"
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
                name="action-buttons"
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

    <Dialog
        overlay-id="scraped-article-selection-prompt"
        title="Prompt do Assistente"
        max-width="800px"
        scrollable
        no-body
        v-model="isSelectionPromptDialogOpen"
    >
        <v-card-text>
            <pre
                class="text-body-2 font-monospace pa-4 ma-0 rounded bg-surface-light text-pre-wrap overflow-x-auto"
            >{{ selectedScrapedArticle?.assistant_selection_prompt }}</pre>
        </v-card-text>

        <template #actions>
            <v-btn
                variant="tonal"
                prepend-icon="mdi-content-copy"
                text="Copiar"
                @click="copySelectionPrompt"
            ></v-btn>
        </template>
    </Dialog>
</template>

<script setup>
    import { ref, computed, nextTick, onUnmounted, watch } from "vue";

    import api from "@/api";

    import Dialog from "@/Components/Dialog.vue";

    import { copyText } from "@/Utils/Common";

    import {
        alertError,
        confirm,
        toastSuccess,
    } from "@/Utils/Alert";

    const props = defineProps({
        forcedReadOnly: Boolean,
    });

    const emit = defineEmits([
        "updated",
        "close",
    ]);

    const isOpen = defineModel({ default: false });

    const isRejectTranslationDialogOpen = ref(false);
    const isSelectionPromptDialogOpen = ref(false);

    const selectedScrapedArticle = ref(null);
    const contentTab = ref("original");

    const contentLoading = ref(false);
    const rejectLoading = ref(false);
    const translating = ref(false);

    const TRANSLATION_POLL_INTERVAL = 3000;
    const TRANSLATION_POLL_MAX_ATTEMPTS = 200;

    let translationPollInterval = null;
    let translationPollAttempts = 0;
    let translationInitialAttempts = 0;

    const readOnly = computed(() => (
        props.forcedReadOnly ||
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

    const hasSelectionPrompt = computed(() => (
        !!selectedScrapedArticle.value?.assistant_selection_prompt
    ));

    const dialogTitle = computed(() => {
        const article = selectedScrapedArticle.value;

        if( !article ) return "";

        if( contentTab.value === "translated" ) {
            return article.translated_title || "";
        }

        return article.title || "";
    });

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
                isOpen.value = false;
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

            toastSuccess("Artigo desativado com sucesso");

            isOpen.value = false;

            emit("updated");
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

            toastSuccess(data.message || "Tradução rejeitada com sucesso");

            isRejectTranslationDialogOpen.value = false;

            emit("updated");
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

            toastSuccess(data.message || "Contagem de tentativas reiniciada com sucesso");

            emit("updated");
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

            toastSuccess("Tradução concluída com sucesso");

            emit("updated");

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

            toastSuccess("Tradução iniciada com sucesso");

            startTranslationPolling();
        } catch (e) {
            console.error(e);

            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao iniciar tradução");

            translating.value = false;
        }
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
            toastSuccess("Texto copiado com sucesso");
        }
    }

    async function copySelectionPrompt() {
        const prompt = selectedScrapedArticle.value?.assistant_selection_prompt;

        if( !prompt ) return;

        const { success, error } = await copyText(prompt);

        if( !success ) {
            alertError("Ooops", error || "Erro ao copiar prompt");
        } else {
            toastSuccess("Prompt copiado com sucesso");
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
            toastSuccess("Texto copiado com sucesso");
        }
    }

    function open(articleOrId) {
        const id = typeof articleOrId === "object"
            ? articleOrId?.id
            : articleOrId;

        if( !id ) return;

        if( typeof articleOrId === "object" ) {
            selectedScrapedArticle.value = articleOrId;
        }

        isOpen.value = true;

        nextTick(async () => {
            await getScrapedArticleById(id);
        });
    }

    function close() {
        stopTranslationPolling();
        isOpen.value = false;
    }

    onUnmounted(() => {
        stopTranslationPolling();
    });

    watch(isOpen, (dialogOpen) => {
        if( !dialogOpen ) {
            stopTranslationPolling();
            emit("close");
        }
    });

    defineExpose({
        open,
        close,
        selectedScrapedArticle,
    });
</script>
