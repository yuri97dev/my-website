<template>
    <Container>
        <v-card
            class="d-flex flex-column w-100 h-100"
            :loading="loading"
        >
            <v-card-item>
                <v-row>
                    <v-col cols="12" lg="8">
                        <v-text-field
                            type="search"
                            label="Pesquisar"
                            prepend-inner-icon="mdi-magnify"
                            clearable
                            v-model="search"
                        ></v-text-field>
                    </v-col>

                    <v-col cols="12" lg="4">
                        <v-text-field
                            type="date"
                            label="Data"
                            append-inner-icon="mdi-refresh"
                            :loading="loading"
                            @click:append-inner="getLogs"
                            v-model="date"
                        ></v-text-field>
                    </v-col>
                </v-row>
            </v-card-item>

            <v-divider></v-divider>

            <template v-if="logs.length > 0">
                <v-virtual-scroll
                    item-key="id"
                    height="400px"
                    style="resize: vertical"
                    :items="filteredLogs"
                >
                    <template #default="{ item }">
                        <v-list-item class="py-4">
                            <span
                                v-if="item.datetime"
                                class="d-flex align-center justify-space-between ga-2 mb-2"
                            >
                                <v-chip
                                    size="small"
                                    variant="tonal"
                                    class="font-monospace"
                                    :color="logLevelColor(item.level)"
                                >
                                    {{ item.level || item.type || "LOG" }}
                                </v-chip>

                                <span class="d-flex align-center ga-2 text-grey">
                                    <v-icon icon="mdi-clock-outline" size="16"></v-icon>
                                    {{ format(item.datetime, "dd/MM/yyyy HH:mm:ss") }}
                                </span>
                            </span>

                            <v-list-item-title class="font-monospace text-wrap">
                                {{ item.message }}
                            </v-list-item-title>

                            <div
                                v-if="item.trace.length > 0"
                                class="mt-2 w-100"
                            >
                                <v-btn
                                    size="small"
                                    variant="tonal"
                                    :prepend-icon="expandedTraces.has(item.id)
                                        ? 'mdi-chevron-up'
                                        : 'mdi-chevron-down'"
                                    :text="expandedTraces.has(item.id)
                                        ? 'Ocultar trace'
                                        : `Ver trace (${item.trace.length})`"
                                    @click="toggleTrace(item.id)"
                                ></v-btn>

                                <pre
                                    v-show="expandedTraces.has(item.id)"
                                    class="text-medium-emphasis font-monospace pa-2 ma-0 mt-2 rounded bg-surface-light overflow-x-auto"
                                >{{ item.trace.join("\n") }}</pre>
                            </div>
                        </v-list-item>

                        <v-divider></v-divider>
                    </template>
                </v-virtual-scroll>
            </template>

            <v-card-item v-else>
                <div
                    style="height: var(--center-height);"
                    class="d-flex flex-column align-center justify-center ga-2"
                >
                    <v-icon
                        icon="mdi-file-search-outline"
                        size="56"
                        color="grey"
                    ></v-icon>

                    <span v-if="loading" class="text-grey">
                        Carregando logs...
                    </span>

                    <span v-else class="text-grey">
                        Nenhum log encontrado para esta data.
                    </span>
                </div>
            </v-card-item>
        </v-card>
    </Container>
</template>

<script setup>
    import { ref, computed, watch, onMounted } from "vue";
    import { format } from "date-fns";

    import api from "@/api";

    import Container from "@/Components/Container.vue";

    import { alertError } from "@/Utils/Alert";
    import { parseLogs, logLevelColor } from "@/Utils/ParseLogs";
    import { formatText } from "@/Utils/Common";

    const search = ref("");
    const date = ref(format(new Date(), "yyyy-MM-dd"));
    const content = ref("");
    const loading = ref(false);
    const expandedTraces = ref(new Set());

    const logs = computed(() => parseLogs(content.value).reverse());

    const filteredLogs = computed(() => {
        const keywords = formatText(search.value || "").split(" ");

        if( keywords.length === 0 ) return logs.value;

        return logs.value.filter(log => {
            return keywords.every(keyword => log.forSearch.includes(keyword));
        });
    });

    function toggleTrace(id) {
        const next = new Set(expandedTraces.value);

        if( next.has(id) ) {
            next.delete(id);
        } else {
            next.add(id);
        }

        expandedTraces.value = next;
    }

    async function getLogs() {
        loading.value = true;
        expandedTraces.value = new Set();

        try {
            const url = route("api.logs.show", date.value);
            const { data } = await api.get(url);

            content.value = data.content;
        } catch (e) {
            content.value = "";
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar logs");
        } finally {
            loading.value = false;
        }
    }

    watch(date, async () => {
        await getLogs();
    });

    onMounted(async () => {
        await getLogs();
    });
</script>
