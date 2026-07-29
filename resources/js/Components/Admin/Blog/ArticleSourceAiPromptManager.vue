<template>
    <Dialog
        overlay-id="article-source-ai-prompt"
        :title="`Prompts de IA — ${articleSource?.name ?? ''}`"
        max-width="700"
        no-body
        @close="handleClose"
        v-model="isDialogOpen"
    >
        <v-card-text>
            <div class="d-flex align-center justify-space-between ga-4 mb-4">
                <v-autocomplete
                    label="Adicionar prompt"
                    item-title="label"
                    item-value="id"
                    clearable
                    hide-details
                    :items="availablePrompts"
                    :loading="loadingPrompts"
                    v-model="selectedPromptId"
                ></v-autocomplete>

                <v-btn
                    icon="mdi-plus"
                    variant="tonal"
                    color="primary"
                    class="rounded"
                    :disabled="!selectedPromptId"
                    @click="addPrompt"
                ></v-btn>
            </div>

            <v-skeleton-loader
                v-if="loading"
                type="list-item-three-line@3"
            ></v-skeleton-loader>

            <v-list
                v-else-if="linkedPrompts.length > 0"
                lines="two"
                border
                slim
            >
                <v-list-item
                    v-for="(prompt, index) in linkedPrompts"
                    :key="prompt.id"
                    :title="`${index + 1}. ${prompt.key}`"
                    :subtitle="formatPromptSubtitle(prompt)"
                    prepend-icon="mdi-text-box-outline"
                >
                    <template #append>
                        <div class="d-flex ga-1">
                            <v-btn
                                size="small"
                                icon="mdi-arrow-up"
                                variant="text"
                                :disabled="index === 0"
                                @click="moveUp(index)"
                            ></v-btn>

                            <v-btn
                                size="small"
                                icon="mdi-arrow-down"
                                variant="text"
                                :disabled="index === linkedPrompts.length - 1"
                                @click="moveDown(index)"
                            ></v-btn>

                            <v-btn
                                size="small"
                                icon="mdi-delete"
                                variant="text"
                                color="error"
                                @click="removePrompt(index)"
                            ></v-btn>
                        </div>
                    </template>
                </v-list-item>
            </v-list>

            <div
                v-else
                class="text-medium-emphasis text-center py-8"
            >
                Nenhum prompt vinculado a esta fonte.
            </div>
        </v-card-text>

        <template #actions>
            <v-btn
                variant="tonal"
                color="primary"
                prepend-icon="mdi-content-save"
                :loading="saving"
                text="Salvar"
                @click="handleSave"
            ></v-btn>
        </template>
    </Dialog>
</template>

<script setup>
    import { ref, computed, nextTick } from "vue";

    import api from "@/api";

    import Dialog from "@/Components/Dialog.vue";

    import { alertError, toastSuccess } from "@/Utils/Alert";

    const emits = defineEmits([
        "update",
    ]);

    const articleSource = ref(null);

    const isDialogOpen = ref(false);
    const hasChanges = ref(false);

    const allPrompts = ref([]);
    const linkedPrompts = ref([]);
    const selectedPromptId = ref(null);

    const loading = ref(false);
    const loadingPrompts = ref(false);
    const saving = ref(false);

    const availablePrompts = computed(() => {
        const linkedIds = new Set(linkedPrompts.value.map((prompt) => prompt.id));

        return allPrompts.value
            .filter((prompt) => !linkedIds.has(prompt.id))
            .map((prompt) => ({
                ...prompt,
                label: `${prompt.ai_provider?.name ?? "Provedor"} · ${prompt.key}`,
            }));
    });

    function formatPromptSubtitle(prompt) {
        const provider = prompt.ai_provider?.name ?? "";
        const model = prompt.model ? `Modelo: ${prompt.model}` : "";

        return [provider, model].filter(Boolean).join(" · ");
    }

    async function getAllPrompts() {
        loadingPrompts.value = true;

        try {
            const url = route("api.ai-prompts.index");
            const { data } = await api.get(url);

            allPrompts.value = data.ai_prompts;
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar prompts disponíveis");
        } finally {
            loadingPrompts.value = false;
        }
    }

    async function getLinkedPrompts() {
        if( !articleSource.value?.id ) return;

        loading.value = true;

        try {
            const url = route("api.article-sources.ai-prompts.index", articleSource.value.id);
            const { data } = await api.get(url);

            linkedPrompts.value = data.ai_prompts;
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar prompts vinculados");
        } finally {
            loading.value = false;
        }
    }

    function addPrompt() {
        if( !selectedPromptId.value ) return;

        const prompt = allPrompts.value.find((item) => item.id === selectedPromptId.value);

        if( !prompt ) return;

        linkedPrompts.value.push({ ...prompt });
        selectedPromptId.value = null;
    }

    function removePrompt(index) {
        linkedPrompts.value.splice(index, 1);
    }

    function moveUp(index) {
        if( index <= 0 ) return;

        const prompts = [...linkedPrompts.value];

        [prompts[index - 1], prompts[index]] = [prompts[index], prompts[index - 1]];

        linkedPrompts.value = prompts;
    }

    function moveDown(index) {
        if( index >= linkedPrompts.value.length - 1 ) return;

        const prompts = [...linkedPrompts.value];

        [prompts[index], prompts[index + 1]] = [prompts[index + 1], prompts[index]];

        linkedPrompts.value = prompts;
    }

    async function handleSave() {
        if( !articleSource.value?.id ) return;

        saving.value = true;

        try {
            const url = route("api.article-sources.ai-prompts.sync", articleSource.value.id);

            await api.put(url, {
                ai_prompt_ids: linkedPrompts.value.map((prompt) => prompt.id),
            });

            toastSuccess("Ok", "Prompts de IA atualizados com sucesso");

            hasChanges.value = true;
            isDialogOpen.value = false;
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao salvar prompts de IA");
        } finally {
            saving.value = false;
        }
    }

    function open(source) {
        articleSource.value = source;
        hasChanges.value = false;
        selectedPromptId.value = null;
        isDialogOpen.value = true;

        nextTick(async () => {
            await Promise.all([
                getAllPrompts(),
                getLinkedPrompts(),
            ]);
        });
    }

    function handleClose() {
        articleSource.value = null;
        linkedPrompts.value = [];
        selectedPromptId.value = null;

        if( !hasChanges.value ) return;

        emits("update");
    }

    defineExpose({
        open,
    });
</script>
