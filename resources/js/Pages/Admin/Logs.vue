<template>
    <Container gapped>
        <v-row>
            <v-col cols="12" lg="8">
                <v-card hover border>
                    <v-text-field
                        type="search"
                        placeholder="Pesquisar"
                        prepend-inner-icon="mdi-magnify"
                        append-inner-icon="mdi-refresh"
                        variant="solo"
                        clearable
                        @click:append-inner="getLogs"
                        v-model="search"
                    ></v-text-field>
                </v-card>
            </v-col>

            <v-col cols="12" lg="4">
                <v-card hover border>
                    <v-date-input
                        variant="solo"
                        prepend-icon=""
                        prepend-inner-icon="mdi-calendar-outline"
                        v-model="date"
                    ></v-date-input>
                </v-card>
            </v-col>
        </v-row>

        <v-card
            class="d-flex flex-column w-100 h-100"
            :loading="loading"
        >
            <template v-if="logs.length > 0">
                <v-virtual-scroll
                    item-key="id"
                    height="400px"
                    :items="filteredLogs"
                >
                    <template #default="{ item }">
                        <v-list-item class="py-4">
                            <span
                                v-if="item.datetime"
                                class="d-flex align-center justify-space-between ga-2 mb-2"
                            >
                                <span class="d-flex align-center ga-2">
                                    <strong class="text-purple-lighten-2">
                                        # {{ item.index + 1 }}
                                    </strong>

                                    <v-chip
                                        size="small"
                                        variant="tonal"
                                        class="font-monospace"
                                        :color="logLevelColor(item.level)"
                                    >
                                        {{ item.level || item.type || "LOG" }}
                                    </v-chip>
                                </span>

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

            <v-card-item v-else class="h-100">
                <div class="d-flex flex-column align-center justify-center ga-2">
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
    const date = ref(new Date());
    const content = ref("");
    const loading = ref(false);
    const expandedTraces = ref(new Set());

    const dateString = computed(() => format(date.value, "yyyy-MM-dd"));

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
            const url = route("api.logs.show", dateString.value);
            const { data } = await api.get(url);

            content.value = data.content;
        } catch (e) {
            // console.log(e);
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
