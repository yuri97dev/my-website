<template>
    <Dialog
        title="Visualizações"
        max-width="900px"
        no-body
        scrollable
        v-model="isOpen"
    >
        <div class="pa-4">
            <v-row class="mb-4">
                <v-col cols="12" sm="6" md="3">
                    <v-card variant="tonal" color="primary">
                        <v-card-text class="text-center">
                            <div class="text-caption text-medium-emphasis">
                                Total de visualizações
                            </div>

                            <div class="text-h5">
                                {{ formatCount(summary.views_count) }}
                            </div>
                        </v-card-text>
                    </v-card>
                </v-col>

                <v-col cols="12" sm="6" md="3">
                    <v-card variant="tonal" color="success">
                        <v-card-text class="text-center">
                            <div class="text-caption text-medium-emphasis">
                                Visitantes únicos
                            </div>

                            <div class="text-h5">
                                {{ formatCount(summary.unique_visitors) }}
                            </div>
                        </v-card-text>
                    </v-card>
                </v-col>

                <v-col cols="12" sm="6" md="3">
                    <v-card variant="tonal">
                        <v-card-text class="text-center">
                            <div class="text-caption text-medium-emphasis">
                                Primeira visualização
                            </div>

                            <div class="text-body-1">
                                {{ formatDateTime(summary.first_viewed_at) }}
                            </div>
                        </v-card-text>
                    </v-card>
                </v-col>

                <v-col cols="12" sm="6" md="3">
                    <v-card variant="tonal">
                        <v-card-text class="text-center">
                            <div class="text-caption text-medium-emphasis">
                                Última visualização
                            </div>

                            <div class="text-body-1">
                                {{ formatDateTime(summary.last_viewed_at) }}
                            </div>
                        </v-card-text>
                    </v-card>
                </v-col>
            </v-row>

            <v-card :loading="loading">
                <v-data-table-server
                    :headers="headers"
                    :items="views"
                    :items-length="pagination.total"
                    :loading="loading"
                    @update:options="getViews"
                    v-model:page="pagination.page"
                    v-model:items-per-page="pagination.perPage"
                >
                    <template #item.viewed_at="{ item }">
                        {{ formatDateTime(item.viewed_at) }}
                    </template>

                    <template #item.visitor="{ item }">
                        {{ item.user?.name || "Anônimo" }}
                    </template>

                    <template #item.referrer="{ item }">
                        <span
                            v-if="item.referrer"
                            class="text-truncate d-inline-block"
                            style="max-width: 280px;"
                            :title="item.referrer"
                        >
                            {{ item.referrer }}
                        </span>

                        <span v-else class="text-medium-emphasis">—</span>
                    </template>
                </v-data-table-server>
            </v-card>
        </div>

        <template #actions>
            <v-btn
                prepend-icon="mdi-refresh"
                text="Atualizar"
                :disabled="loading"
                @click="getViews"
            ></v-btn>
        </template>
    </Dialog>
</template>

<script setup>
    import { ref, reactive, nextTick } from "vue";

    import api from "@/api";

    import Dialog from "@/Components/Dialog.vue";

    import { alertError } from "@/Utils/Alert";
    import { formatDate } from "@/Utils/Common";

    const CELL_PROPS = {
        class: "text-no-wrap",
    };

    const isOpen = ref(false);
    const loading = ref(false);
    const activePostId = ref(null);

    const summary = ref({
        views_count: 0,
        unique_visitors: 0,
        first_viewed_at: null,
        last_viewed_at: null,
    });

    const views = ref([]);

    const pagination = reactive({
        page: 1,
        perPage: 15,
        total: 0,
        lastPage: 1,
    });

    const headers = [
        {
            title: "Data",
            key: "viewed_at",
            sortable: false,
            cellProps: CELL_PROPS,
        },
        {
            title: "Visitante",
            key: "visitor",
            sortable: false,
            cellProps: CELL_PROPS,
        },
        {
            title: "Origem",
            key: "referrer",
            sortable: false,
        },
    ];

    function formatCount(value) {
        return Number(value || 0).toLocaleString("pt-BR");
    }

    function formatDateTime(value) {
        if( !value ) return "—";
        return formatDate(value, "dd/MM/yyyy HH:mm");
    }

    async function getViews(options = {}) {
        if( !activePostId.value ) return;

        if( options.page ) pagination.page = options.page;
        if( options.itemsPerPage ) pagination.perPage = options.itemsPerPage;

        loading.value = true;

        try {
            const url = route("api.blog.posts.views.index", activePostId.value);

            const { data } = await api.get(url, {
                params: {
                    page: pagination.page,
                    per_page: pagination.perPage,
                },
            });

            summary.value = data.summary;
            views.value = data.views;

            pagination.total = data.total;
            pagination.lastPage = data.last_page;
            pagination.page = data.current_page;
            pagination.perPage = data.per_page;
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar visualizações");
        } finally {
            loading.value = false;
        }
    }

    function open(postId) {
        activePostId.value = postId;

        isOpen.value = true;

        nextTick(async () => {
            pagination.page = 1;
            await getViews();
        });
    }

    defineExpose({
        open,
    });
</script>
