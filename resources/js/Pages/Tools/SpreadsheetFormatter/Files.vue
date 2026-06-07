<template>
    <Container centered>
        <div style="width: 100%; max-width: var(--center-width);">
            <v-alert
                type="info"
                variant="tonal"
                icon="mdi-information"
                class="mb-4"
            >
                <span v-if="files.length === 0">
                    <template v-if="loading">
                        Carregando arquivos...
                    </template>

                    <template v-else>
                        Nenhum arquivo encontrado.
                    </template>
                </span>

                <span v-else>
                    Os arquivos abaixo serão processados e
                    excluídos automaticamente após 30 minutos.
                    Após esse período, eles não estarão mais disponíveis.
                </span>
            </v-alert>

            <v-card height="var(--center-height)">
                <v-list class="overflow-y-auto h-100">
                    <v-list-item
                        v-for="(file, index) in files"
                        :key="index"
                        prepend-icon="mdi-file-outline"
                        lines="two"
                    >
                        <v-list-item-title
                            :title="file.original_name"
                            class="text-wrap"
                        >
                            {{ file.original_name }}
                        </v-list-item-title>

                        <v-list-item-subtitle>
                            {{ formatBytes(file.size_bytes) }}
                        </v-list-item-subtitle>

                        <template #append>
                            <v-menu offset="8" location="bottom end">
                                <template #activator="{ props }">
                                    <v-btn
                                        icon
                                        variant="tonal"
                                        color="primary"
                                        class="rounded"
                                        v-bind="props"
                                    >
                                        <v-progress-circular
                                            v-if="file.loading"
                                            color="primary"
                                            :indeterminate="file.progress == 0"
                                            :model-value="file.progress"
                                        ></v-progress-circular>

                                        <v-icon
                                            v-else
                                            icon="mdi-dots-vertical"
                                        ></v-icon>
                                    </v-btn>
                                </template>

                                <v-list
                                    v-if="!file.loading"
                                    slim
                                    border
                                >
                                    <v-list-item
                                        v-if="file.status === 'done'"
                                        prepend-icon="mdi-download"
                                        title="Baixar"
                                        @click="downloadFile(file.uuid)"
                                    ></v-list-item>

                                    <v-list-item
                                        prepend-icon="mdi-delete"
                                        title="Excluir"
                                        @click="deleteFile(file.uuid)"
                                    ></v-list-item>
                                </v-list>
                            </v-menu>
                        </template>
                    </v-list-item>
                </v-list>
            </v-card>
        </div>
    </Container>
</template>

<script setup>
    import { ref, computed, onMounted, onBeforeUnmount } from "vue";

    import api from "@/api";

    import Container from "@/Components/Container.vue";

    import { alertError } from "@/Utils/Alert";
    import { formatBytes } from "@/Utils/Common";

    const files = ref([]);
    const loading = ref(false);

    let timeout = null;

    function downloadFile(uuid) {
        const url = route("tools.spreadsheet-formatter.download", uuid);
        window.open(url, "_blank");
    }

    async function deleteFile(uuid) {
        loading.value = true;

        try {
            const url = route("tools.spreadsheet-formatter.files.destroy", uuid);
            await api.delete(url);
        } catch (e) {
            alertError("Ooops", "Erro ao excluir arquivo");
        }

        await getFiles();
    }

    async function getFiles() {
        clearTimeout(timeout);

        loading.value = true;

        try {
            const url = route("tools.spreadsheet-formatter.files.status");
            const { data } = await api.get(url);

            files.value = data.files.map(file => ({
                ...file,
                loading: (
                    file.status === "queued" ||
                    file.status === "processing"
                ),
            }));
        } catch (e) {
            alertError("Ooops", "Erro ao buscar arquivos");
        } finally {
            loading.value = false;
        }

        timeout = setTimeout(async () => {
            await getFiles();
        }, 3000);
    }

    onMounted(async () => {
        await getFiles();
    });

    onBeforeUnmount(() => {
        clearTimeout(timeout);
    });
</script>
