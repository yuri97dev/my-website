<template>
    <BottomSheet
        overlay-id="blog-post-views"
        title="Visualizações"
        height="80dvh"
        :actions="sheetActions"
        v-model="isOpen"
    >
        <div>
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
        </div>

        <VirtualRemoteTable
            v-if="activePostId"
            ref="tableRef"
            items-key="views"
            border
            :key="activePostId"
            :href="route('api.blog.posts.views.index', activePostId)"
            :headers="headers"
            @loaded="handleLoaded"
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
        </VirtualRemoteTable>
    </BottomSheet>
</template>

<script setup>
    import { ref, computed } from "vue";

    import BottomSheet from "@/Components/BottomSheet.vue";
    import VirtualRemoteTable from "@/Components/VirtualRemoteTable.vue";

    import { formatDate } from "@/Utils/Common";

    const CELL_PROPS = {
        class: "text-no-wrap",
    };

    const isOpen = ref(false);
    const activePostId = ref(null);
    const tableRef = ref(null);

    const summary = ref({
        views_count: 0,
        unique_visitors: 0,
        first_viewed_at: null,
        last_viewed_at: null,
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

    const sheetActions = computed(() => [
        {
            key: "refresh",
            prependIcon: "mdi-refresh",
            text: "Atualizar",
            onClick: refresh,
        },
    ]);

    function formatCount(value) {
        return Number(value || 0).toLocaleString("pt-BR");
    }

    function formatDateTime(value) {
        if( !value ) return "—";
        return formatDate(value, "dd/MM/yyyy HH:mm");
    }

    function handleLoaded(data) {
        summary.value = data.summary;
    }

    function refresh() {
        tableRef.value?.fetchItems(1);
    }

    function open(postId) {
        activePostId.value = postId;
        isOpen.value = true;
    }

    defineExpose({
        open,
    });
</script>
