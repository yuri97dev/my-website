<template>
    <Container title="Embeddings Ollama" gapped>
        <v-row v-if="loading">
            <v-col cols="12">
                <v-skeleton-loader type="article"></v-skeleton-loader>
            </v-col>
        </v-row>

        <template v-else>
            <v-alert
                v-if="ollamaError"
                type="warning"
                variant="tonal"
                :text="ollamaError"
            ></v-alert>

            <v-card variant="tonal">
                <v-card-item>
                    <v-card-title>Busca Semântica</v-card-title>

                    <v-card-subtitle>
                        Modelo Atual: <strong>{{ embedModel }}</strong>
                        · Dimensão: {{ embedDimension }}
                    </v-card-subtitle>
                </v-card-item>

                <v-divider></v-divider>

                <v-card-text class="d-flex flex-wrap ga-4">
                    <v-chip variant="tonal">
                        Trechos: {{ postChunksCount }}
                    </v-chip>

                    <v-chip variant="tonal">
                        Cache de Busca: {{ searchCachesCount }}
                    </v-chip>
                </v-card-text>
            </v-card>

            <v-row>
                <v-col cols="12" lg="6">
                    <v-card>
                        <v-card-text>
                            <v-select
                                label="Modelo Instalado"
                                :items="modelItems"
                                item-title="title"
                                item-value="value"
                                :disabled="saving || pulling || !!ollamaError"
                                v-model="selectedModel"
                            ></v-select>
                        </v-card-text>

                        <v-divider></v-divider>

                        <v-card-actions>
                            <v-btn
                                color="primary"
                                text="Aplicar Modelo"
                                prepend-icon="mdi-content-save"
                                :loading="saving"
                                :disabled="!selectedModel || selectedModel === embedModel || pulling"
                                @click="applyModel"
                            ></v-btn>
                        </v-card-actions>
                    </v-card>
                </v-col>

                <v-col cols="12" lg="6">
                    <v-card>
                        <v-card-text>
                            <v-text-field
                                label="Baixar Modelo (ollama pull)"
                                :disabled="pulling || saving"
                                v-model="pullModelName"
                            ></v-text-field>
                        </v-card-text>

                        <v-divider></v-divider>

                        <v-card-actions>
                            <v-btn
                                prepend-icon="mdi-download"
                                text="Baixar"
                                :loading="pulling"
                                :disabled="!pullModelName || saving"
                                @click="pullModel"
                            ></v-btn>
                        </v-card-actions>
                    </v-card>
                </v-col>
            </v-row>
        </template>
    </Container>
</template>

<script setup>
    import { computed, onMounted, ref } from "vue";

    import api from "@/api";

    import Container from "@/Components/Container.vue";

    import { alertError, confirm, toastSuccess } from "@/Utils/Alert";

    const loading = ref(true);
    const saving = ref(false);
    const pulling = ref(false);

    const embedModel = ref("");
    const embedDimension = ref(1024);
    const postChunksCount = ref(0);
    const searchCachesCount = ref(0);
    const ollamaError = ref(null);
    const models = ref([]);

    const selectedModel = ref(null);
    const pullModelName = ref("");

    const modelItems = computed(() => models.value.map((model) => ({
        title: model.name,
        value: model.name,
    })));

    async function fetchState() {
        loading.value = true;

        try {
            const url = route("api.ollama-embeddings.show");
            const { data } = await api.get(url);

            embedModel.value = data.embed_model;
            embedDimension.value = data.embed_dimension;
            postChunksCount.value = data.post_chunks_count;
            searchCachesCount.value = data.search_embedding_caches_count;
            ollamaError.value = data.ollama_error;
            models.value = data.models ?? [];
            selectedModel.value = data.embed_model;
        } catch {
            alertError("Erro", "Não foi possível carregar as configurações de embedding.");
        } finally {
            loading.value = false;
        }
    }

    async function pullModel() {
        const model = pullModelName.value.trim();

        if( !model ) return;

        pulling.value = true;

        try {
            const url = route("api.ollama-embeddings.pull");
            const { data } = await api.post(url, { model });

            toastSuccess(data.message ?? "Modelo baixado.");
            pullModelName.value = "";
            await fetchState();
            selectedModel.value = model;
        } catch (error) {
            const message = error?.response?.data?.message
                ?? "Não foi possível baixar o modelo.";

            alertError("Erro", message);
        } finally {
            pulling.value = false;
        }
    }

    async function applyModel() {
        if( !selectedModel.value || selectedModel.value === embedModel.value ) return;

        const result = await confirm(
            "Alterar modelo de embedding?",
            "Todos os trechos semânticos e caches de busca serão removidos. Os trechos serão gerados novamente pelo agendador.",
        );

        if( !result.isConfirmed ) return;

        saving.value = true;

        try {
            const url = route("api.ollama-embeddings.update");
            const { data } = await api.put(url, {
                embed_model: selectedModel.value,
            });

            embedModel.value = data.embed_model;
            embedDimension.value = data.embed_dimension;
            postChunksCount.value = data.post_chunks_count;
            searchCachesCount.value = data.search_embedding_caches_count;
            toastSuccess(data.message ?? "Modelo atualizado.");
        } catch (error) {
            const message = error?.response?.data?.message
                ?? "Não foi possível atualizar o modelo.";

            alertError("Erro", message);
        } finally {
            saving.value = false;
        }
    }

    onMounted(fetchState);
</script>
