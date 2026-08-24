<template>
    <Container title="Logs" gapped>
        <v-row>
            <v-col cols="12" lg="4" xl="6">
                <v-card hover>
                    <v-text-field
                        type="search"
                        placeholder="Pesquisar"
                        prepend-inner-icon="mdi-magnify"
                        variant="solo"
                        v-model="search"
                    >
                        <template #append-inner>
                            <div class="d-flex align-center ga-2">
                                <v-btn
                                    size="small"
                                    icon="mdi-refresh"
                                    variant="tonal"
                                    color="primary"
                                    @click="getLogs"
                                ></v-btn>

                                <v-btn
                                    size="small"
                                    icon="mdi-close"
                                    variant="tonal"
                                    @click="search = ''"
                                ></v-btn>
                            </div>
                        </template>
                    </v-text-field>
                </v-card>
            </v-col>

            <v-col cols="12" lg="4" xl="3">
                <LogFileSelect v-model="selectedFile" />
            </v-col>

            <v-col cols="12" lg="4" xl="3">
                <v-card hover>
                    <DateField
                        variant="solo"
                        :warn-non-business-day="false"
                        :disabled="!!selectedFile"
                        v-model="date"
                    ></DateField>
                </v-card>
            </v-col>
        </v-row>

        <v-card
            class="d-flex flex-column w-100 h-100"
            :loading="loading"
        >
            <template v-if="logEntries.length > 0">
                <v-virtual-scroll
                    item-key="id"
                    height="400px"
                    :items="filteredLogEntries"
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
                                    {{ formatDate(item.datetime, "dd/MM/yyyy HH:mm:ss") }}
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

            <template v-else-if="logLines.length > 0">
                <v-virtual-scroll
                    item-key="id"
                    height="400px"
                    :items="filteredLogLines"
                >
                    <template #default="{ item }">
                        <v-list-item class="py-2">
                            <v-list-item-title class="font-monospace text-wrap">
                                {{ item.message }}
                            </v-list-item-title>
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
                        Nenhum log encontrado.
                    </span>
                </div>
            </v-card-item>
        </v-card>

        <v-fab
            app
            icon="mdi-delete-sweep"
            size="large"
            color="error"
            :disabled="loading"
            @click="confirmClearLogs"
        ></v-fab>

    </Container>
</template>

<script setup>
    import { ref, computed, watch, onMounted } from "vue";

    import api from "@/api";

    import Container from "@/Components/Container.vue";
    import DateField from "@/Components/DateField.vue";
    import LogFileSelect from "@/Components/Inputs/LogFileSelect.vue";

    import { alertError, confirmDelete, toastSuccess } from "@/Utils/Alert";
    import { parseLogEntries, parseLogLines, logLevelColor } from "@/Utils/ParseLogs";
    import { formatDate, formatText } from "@/Utils/Common";

    const search = ref("");
    const date = ref(formatDate(new Date(), "yyyy-MM-dd"));
    const selectedFile = ref(null);
    const content = ref("");
    const loading = ref(false);
    const expandedTraces = ref(new Set());

    const logEntries = computed(() => parseLogEntries(content.value));

    const logLines = computed(() => {
        if( logEntries.value.length > 0 ) {
            return [];
        }

        return parseLogLines(content.value);
    });

    const filteredLogEntries = computed(() => {
        const keywords = formatText(search.value || "").split(" ");

        if( keywords.length === 0 ) return logEntries.value;

        return logEntries.value.filter(entry => {
            return keywords.every(keyword => entry.forSearch.includes(keyword));
        });
    });

    const filteredLogLines = computed(() => {
        const keywords = formatText(search.value || "").split(" ");

        if( keywords.length === 0 ) return logLines.value;

        return logLines.value.filter(line => {
            return keywords.every(keyword => line.forSearch.includes(keyword));
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
            const url = selectedFile.value
                ? route("api.logs.files.show", selectedFile.value.name)
                : route("api.logs.files.daily.show", date.value);

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

    async function confirmClearLogs() {
        const { isConfirmed } = await confirmDelete(
            "Limpar Logs",
            "Limpar os logs?",
            { confirmButtonText: "Limpar" },
        );

        if( !isConfirmed ) return;

        await clearLogs();
    }

    async function clearLogs() {
        loading.value = true;
        expandedTraces.value = new Set();

        try {
            const url = selectedFile.value
                ? route("api.logs.files.clear", selectedFile.value.name)
                : route("api.logs.files.daily.clear", date.value);

            await api.delete(url);

            content.value = "";

            toastSuccess("Ok", "Logs limpos com sucesso.");
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao limpar logs");
        } finally {
            loading.value = false;
        }
    }

    watch(date, async () => {
        if( selectedFile.value ) return;
        await getLogs();
    });

    watch(selectedFile, async () => {
        await getLogs();
    });

    onMounted(async () => {
        await getLogs();
    });
</script>
