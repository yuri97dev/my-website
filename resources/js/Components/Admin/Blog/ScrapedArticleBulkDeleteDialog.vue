<template>
    <Dialog
        overlay-id="scraped-article-bulk-delete"
        title="Excluir Artigos em Massa"
        max-width="560px"
        no-body
        scrollable
        :loading="deleting"
        v-model="isDialogOpen"
    >
        <v-card-text>
            <v-alert
                type="info"
                variant="tonal"
                class="mb-4"
            >
                Apenas artigos <strong>não publicados</strong> serão excluídos permanentemente.
                Informe ao menos um filtro abaixo.
            </v-alert>

            <v-alert
                v-if="hasFilters"
                type="warning"
                variant="tonal"
                class="mb-4"
            >
                <template v-if="loadingCount">
                    Calculando quantidade de artigos...
                </template>

                <template v-else>
                    {{ deleteCount }} artigo(s) serão excluídos permanentemente.
                </template>
            </v-alert>

            <v-alert
                v-else
                type="info"
                variant="tonal"
                class="mb-4"
            >
                Selecione ao menos um filtro para visualizar a quantidade de artigos.
            </v-alert>

            <v-row>
                <v-col cols="12">
                    <v-select
                        label="Fonte"
                        clearable
                        item-title="name"
                        item-value="id"
                        :items="articleSources"
                        :loading="loadingArticleSources"
                        v-model="filters.article_source_id"
                    ></v-select>
                </v-col>

                <v-col cols="12" sm="6">
                    <DateField
                        label="Data inicial"
                        clearable
                        v-model="filters.date_from"
                    ></DateField>
                </v-col>

                <v-col cols="12" sm="6">
                    <DateField
                        label="Data final"
                        clearable
                        :disabled="!filters.date_from"
                        v-model="filters.date_to"
                    ></DateField>
                </v-col>

                <v-col cols="12">
                    <v-checkbox
                        label="Apenas artigos não traduzidos"
                        hide-details
                        v-model="filters.untranslated_only"
                    ></v-checkbox>
                </v-col>
            </v-row>
        </v-card-text>

        <template #actions>
            <v-btn
                variant="tonal"
                color="error"
                prepend-icon="mdi-delete-sweep"
                text="Excluir"
                :disabled="!canDelete"
                :loading="deleting"
                @click="confirmDelete"
            ></v-btn>
        </template>
    </Dialog>
</template>

<script setup>
    import { ref, reactive, computed, nextTick, watch } from "vue";

    import api from "@/api";

    import DateField from "@/Components/DateField.vue";
    import Dialog from "@/Components/Dialog.vue";

    import {
        alertError,
        confirmDelete as confirmDeleteDialog,
        toastSuccess,
    } from "@/Utils/Alert";

    const emit = defineEmits([
        "deleted",
    ]);

    const isDialogOpen = ref(false);
    const articleSources = ref([]);
    const defaultArticleSourceId = ref(null);

    const loadingArticleSources = ref(false);
    const loadingCount = ref(false);
    const deleting = ref(false);
    const deleteCount = ref(0);

    const filters = reactive({
        article_source_id: null,
        date_from: null,
        date_to: null,
        untranslated_only: false,
    });

    const hasFilters = computed(() => (
        !!filters.article_source_id ||
        !!filters.date_from ||
        filters.untranslated_only
    ));

    const canDelete = computed(() => (
        hasFilters.value &&
        !loadingCount.value &&
        deleteCount.value > 0 &&
        !deleting.value
    ));

    watch(
        () => filters.date_from,
        (dateFrom) => {
            if( !dateFrom ) {
                filters.date_to = null;
            }
        },
    );

    watch(
        () => [
            filters.article_source_id,
            filters.date_from,
            filters.date_to,
            filters.untranslated_only,
        ],
        async () => {
            if( !isDialogOpen.value ) return;

            await getDeleteCount();
        },
    );

    function getPayload() {
        const payload = {};

        if( filters.article_source_id ) {
            payload.article_source_id = filters.article_source_id;
        }

        if( filters.date_from ) {
            payload.date_from = filters.date_from;
        }

        if( filters.date_from && filters.date_to ) {
            payload.date_to = filters.date_to;
        }

        if( filters.untranslated_only ) {
            payload.untranslated_only = true;
        }

        return payload;
    }

    function resetFilters() {
        filters.article_source_id = defaultArticleSourceId.value;
        filters.date_from = null;
        filters.date_to = null;
        filters.untranslated_only = false;
        deleteCount.value = 0;
    }

    async function getArticleSources() {
        loadingArticleSources.value = true;

        try {
            const url = route("api.article-sources.index");

            const { data } = await api.get(url, {
                params: { per_page: 100 },
            });

            articleSources.value = data.article_sources;
        } catch (e) {
            console.error(e);
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar fontes");
        } finally {
            loadingArticleSources.value = false;
        }
    }

    async function getDeleteCount() {
        if( !hasFilters.value ) {
            deleteCount.value = 0;
            return;
        }

        loadingCount.value = true;

        try {
            const url = route("api.scraped-articles.bulk-delete.count");

            const { data } = await api.get(url, {
                params: getPayload(),
            });

            deleteCount.value = data.count;
        } catch (e) {
            console.error(e);
            deleteCount.value = 0;

            const { message, errors } = e?.response?.data || {};

            const validationError = (
                errors?.date_to?.[0] ||
                errors?.date_from?.[0] ||
                errors?.filters?.[0]
            );

            alertError("Ooops", message || validationError || "Erro ao calcular exclusão em massa");
        } finally {
            loadingCount.value = false;
        }
    }

    async function confirmDelete() {
        if( !canDelete.value ) return;

        const { isConfirmed } = await confirmDeleteDialog(
            "Confirmar exclusão",
            `${deleteCount.value} artigo(s) serão excluídos permanentemente. Esta ação não pode ser desfeita.`,
        );

        if( !isConfirmed ) return;

        await deleteScrapedArticles();
    }

    async function deleteScrapedArticles() {
        deleting.value = true;

        try {
            const url = route("api.scraped-articles.bulk-delete");

            const { data } = await api.delete(url, {
                data: getPayload(),
            });

            toastSuccess(data.message || "Artigos excluídos com sucesso");

            isDialogOpen.value = false;

            emit("deleted");
        } catch (e) {
            console.error(e);

            const { message, errors } = e?.response?.data || {};

            const validationError = (
                errors?.date_to?.[0] ||
                errors?.date_from?.[0] ||
                errors?.filters?.[0]
            );

            alertError("Ooops", message || validationError || "Erro ao excluir artigos");
        } finally {
            deleting.value = false;
        }
    }

    function open(articleSource = null) {
        defaultArticleSourceId.value = articleSource?.id || null;

        resetFilters();

        isDialogOpen.value = true;

        if( articleSources.value.length === 0 ) {
            getArticleSources();
        }

        nextTick(async () => {
            await getDeleteCount();
        });
    }

    defineExpose({
        open,
    });
</script>
