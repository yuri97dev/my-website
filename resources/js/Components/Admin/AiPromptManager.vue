<template>
    <v-dialog
        max-width="700"
        @after-leave="handleClose"
        v-model="isDialogListOpen"
    >
        <v-card>
            <v-card-title>
                Prompts — {{ aiProvider?.name ?? "" }}
            </v-card-title>

            <v-list
                lines="two"
                style="height: 400px; overflow-y: auto;"
            >
                <v-skeleton-loader
                    v-if="loading"
                    type="list-item-three-line@3"
                ></v-skeleton-loader>

                <v-list-item
                    v-for="prompt in prompts"
                    :key="prompt.id"
                    :title="prompt.key"
                    :subtitle="formatPromptSubtitle(prompt)"
                    prepend-icon="mdi-text-box-outline"
                    slim
                >
                    <template #append>
                        <v-menu offset="8" location="bottom end">
                            <template v-slot:activator="{ props }">
                                <v-btn
                                    size="small"
                                    icon="mdi-dots-vertical"
                                    variant="tonal"
                                    color="primary"
                                    class="rounded"
                                    v-bind="props"
                                ></v-btn>
                            </template>

                            <v-list border slim>
                                <v-list-item
                                    prepend-icon="mdi-pencil"
                                    title="Editar"
                                    @click="editPrompt(prompt)"
                                ></v-list-item>

                                <v-list-item
                                    prepend-icon="mdi-delete"
                                    title="Excluir"
                                    @click="confirmRemovePrompt(prompt)"
                                ></v-list-item>
                            </v-list>
                        </v-menu>
                    </template>
                </v-list-item>
            </v-list>

            <v-divider></v-divider>

            <v-card-actions>
                <v-btn
                    variant="tonal"
                    color="primary"
                    prepend-icon="mdi-plus"
                    @click="openCreate"
                >
                    Criar Prompt
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <v-dialog
        max-width="700"
        @after-leave="resetForm"
        v-model="isDialogFormOpen"
    >
        <v-card>
            <v-card-title>
                {{ form.id ? "Editar Prompt" : "Criar Prompt" }}
            </v-card-title>

            <v-card-item>
                <v-text-field
                    label="Chave"
                    hint="Identificador do prompt (ex: blog-post-summary)"
                    persistent-hint
                    class="mb-4"
                    autofocus
                    required
                    :error-messages="errors.key"
                    v-model="form.key"
                ></v-text-field>

                <v-text-field
                    label="Modelo"
                    hint="Modelo de IA a ser usado (ex: deepseek-v4-flash, gpt-4o)"
                    persistent-hint
                    class="mb-4"
                    required
                    :error-messages="errors.model"
                    v-model="form.model"
                ></v-text-field>

                <v-textarea
                    label="Conteúdo"
                    rows="12"
                    auto-grow
                    required
                    :error-messages="errors.content"
                    v-model="form.content"
                ></v-textarea>
            </v-card-item>

            <v-divider></v-divider>

            <v-card-actions>
                <v-btn
                    variant="tonal"
                    color="primary"
                    prepend-icon="mdi-content-save"
                    :loading="saving"
                    @click="handleSave"
                >
                    {{ form.id ? "Salvar" : "Criar" }}
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <v-dialog
        max-width="500"
        v-model="isConfirmRemoveDialogOpen"
    >
        <v-card>
            <v-card-title>Excluir Prompt</v-card-title>

            <v-card-text>
                O prompt <strong class="text-primary">{{ selectedPrompt?.key ?? "" }}</strong>
                será removido permanentemente. Essa ação não pode ser desfeita.
            </v-card-text>

            <v-card-actions>
                <v-btn
                    variant="tonal"
                    color="error"
                    prepend-icon="mdi-delete"
                    :loading="saving"
                    @click="removePrompt(selectedPrompt)"
                >
                    Excluir
                </v-btn>

                <v-btn
                    prepend-icon="mdi-close"
                    @click="isConfirmRemoveDialogOpen = false"
                >
                    Cancelar
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script setup>
    import { ref, reactive, nextTick } from "vue";

    import api from "@/api";

    import { alertError, toastSuccess } from "@/Utils/Alert";

    const emits = defineEmits([
        "update",
    ]);

    const form = reactive({
        id: null,
        key: "",
        model: "",
        content: "",
    });

    const errors = ref({});

    const aiProvider = ref(null);

    const isDialogListOpen = ref(false);
    const isDialogFormOpen = ref(false);
    const isConfirmRemoveDialogOpen = ref(false);

    const hasChanges = ref(false);

    const prompts = ref([]);
    const selectedPrompt = ref(null);

    const loading = ref(false);
    const saving = ref(false);

    function truncateContent(content) {
        if( !content ) return "";
        return content.length > 120 ? `${content.slice(0, 120)}...` : content;
    }

    function formatPromptSubtitle(prompt) {
        const model = prompt.model ? `Modelo: ${prompt.model}` : "";
        const content = truncateContent(prompt.content);

        return [model, content].filter(Boolean).join(" · ");
    }

    async function getPrompts() {
        if( !aiProvider.value?.id ) return;

        loading.value = true;

        try {
            const url = route("api.ai-providers.prompts.index", aiProvider.value.id);
            const { data } = await api.get(url);

            prompts.value = data.ai_prompts;
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar prompts");
        } finally {
            loading.value = false;
        }
    }

    async function createPrompt() {
        errors.value = {};
        saving.value = true;

        try {
            const url = route("api.ai-providers.prompts.store", aiProvider.value.id);
            await api.post(url, {
                key: form.key,
                model: form.model,
                content: form.content,
            });

            toastSuccess("Ok", "Prompt criado com sucesso");

            isDialogFormOpen.value = false;
            hasChanges.value = true;
        } catch (e) {
            console.log(e);
            const { message, errors: responseErrors } = e?.response?.data || {};
            errors.value = responseErrors || {};
            alertError("Ooops", message || "Erro ao criar prompt");
        } finally {
            saving.value = false;
        }

        await getPrompts();
    }

    async function updatePrompt() {
        errors.value = {};
        saving.value = true;

        try {
            const url = route("api.ai-prompts.update", form.id);
            await api.put(url, {
                key: form.key,
                model: form.model,
                content: form.content,
            });

            toastSuccess("Ok", "Prompt atualizado com sucesso");

            isDialogFormOpen.value = false;
            hasChanges.value = true;
        } catch (e) {
            const { message, errors: responseErrors } = e?.response?.data || {};
            errors.value = responseErrors || {};
            alertError("Ooops", message || "Erro ao atualizar prompt");
        } finally {
            saving.value = false;
        }

        await getPrompts();
    }

    async function handleSave() {
        if( form.id ) {
            await updatePrompt();
        } else {
            await createPrompt();
        }
    }

    async function removePrompt(prompt) {
        saving.value = true;

        try {
            const url = route("api.ai-prompts.destroy", prompt.id);
            await api.delete(url);

            toastSuccess("Ok", "Prompt removido com sucesso");

            isConfirmRemoveDialogOpen.value = false;
            hasChanges.value = true;

            selectedPrompt.value = null;
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao remover prompt");
        } finally {
            saving.value = false;
        }

        await getPrompts();
    }

    function confirmRemovePrompt(prompt) {
        selectedPrompt.value = prompt;
        isConfirmRemoveDialogOpen.value = true;
    }

    function openList(provider) {
        aiProvider.value = provider;
        hasChanges.value = false;
        isDialogListOpen.value = true;

        nextTick(async () => {
            await getPrompts();
        });
    }

    function openCreate() {
        errors.value = {};
        isDialogFormOpen.value = true;
    }

    function editPrompt(prompt) {
        errors.value = {};
        form.id = prompt.id;
        form.key = prompt.key;
        form.model = prompt.model;
        form.content = prompt.content;

        isDialogFormOpen.value = true;
    }

    function resetForm() {
        errors.value = {};
        form.id = null;
        form.key = "";
        form.model = "";
        form.content = "";
    }

    function handleClose() {
        aiProvider.value = null;
        prompts.value = [];

        if( !hasChanges.value ) return;
        emits("update");
    }

    defineExpose({
        openList,
    });
</script>
