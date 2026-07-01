<template>
    <BottomSheet
        :title="isEditing ? 'Editar Provedor de IA' : 'Novo Provedor de IA'"
        @close="handleClose"
        v-model="isDialogOpen"
    >
        <v-row>
            <v-col cols="12" lg="6">
                <v-text-field
                    label="Nome"
                    :error-messages="errors.name"
                    v-model="form.name"
                ></v-text-field>
            </v-col>

            <v-col cols="12" lg="6">
                <v-select
                    label="Provedor"
                    :items="PROVIDERS"
                    :error-messages="errors.provider"
                    v-model="form.provider"
                ></v-select>
            </v-col>

            <v-col cols="12" lg="6">
                <v-text-field
                    label="Chave de API"
                    :type="showApiKey ? 'text' : 'password'"
                    :hint="isEditing ? 'Deixe em branco para manter a chave atual' : ''"
                    :persistent-hint="isEditing"
                    :error-messages="errors.api_key"
                    :append-inner-icon="showApiKey ? 'mdi-eye-off' : 'mdi-eye'"
                    @click:append-inner="showApiKey = !showApiKey"
                    v-model="form.api_key"
                ></v-text-field>
            </v-col>

            <v-col cols="12" sm="12">
                <v-switch
                    label="Ativo"
                    color="primary"
                    :error-messages="errors.is_enabled"
                    v-model="form.is_enabled"
                ></v-switch>
            </v-col>
        </v-row>

        <template #actions>
            <slot
                v-if="isEditing"
                name="action-buttons"
            ></slot>

            <v-btn
                variant="tonal"
                color="primary"
                prepend-icon="mdi-content-save"
                :loading="loading"
                text="Salvar"
                @click="handleSave"
            ></v-btn>
        </template>
    </BottomSheet>
</template>

<script setup>
    import { ref, computed, nextTick } from "vue";

    import api from "@/api";

    import BottomSheet from "@/Components/BottomSheet.vue";

    import { alertError, toastSuccess } from "@/Utils/Alert";

    const PROVIDERS = [
        { title: "OpenAI", value: "openai" },
        { title: "Anthropic", value: "anthropic" },
        { title: "Google Gemini", value: "gemini" },
        { title: "Ollama", value: "ollama" },
        { title: "Mistral", value: "mistral" },
        { title: "DeepSeek", value: "deepseek" },
    ];

    const emit = defineEmits([
        "close",
    ]);

    const isDialogOpen = ref(false);
    const showApiKey = ref(false);

    const loading = ref(false);
    const errors = ref({});

    const aiProvider = ref({});
    const form = ref({});

    const isEditing = computed(() => !!aiProvider.value.id);

    function buildPayload() {
        const payload = {
            name: form.value.name,
            provider: form.value.provider,
            is_enabled: form.value.is_enabled,
        };

        if( form.value.api_key ) {
            payload.api_key = form.value.api_key;
        }

        return payload;
    }

    async function updateAiProvider() {
        errors.value = {};
        loading.value = true;

        try {
            const url = route("api.ai-providers.update", aiProvider.value.id);
            const { data } = await api.put(url, buildPayload());

            toastSuccess("Ok", "Provedor de IA atualizado com sucesso");

            aiProvider.value = data.ai_provider;

            form.value = {
                ...form.value,
                ...data.ai_provider,
                api_key: null,
            };
        } catch (e) {
            const { message, errors: responseErrors } = e?.response?.data || {};
            errors.value = responseErrors || {};
            alertError("Ooops", message || "Erro ao atualizar provedor de IA");
        } finally {
            loading.value = false;
        }
    }

    async function createAiProvider() {
        errors.value = {};
        loading.value = true;

        try {
            const url = route("api.ai-providers.store");
            const { data } = await api.post(url, buildPayload());

            toastSuccess("Ok", "Provedor de IA criado com sucesso");

            aiProvider.value = data.ai_provider;

            form.value = {
                ...form.value,
                ...data.ai_provider,
                api_key: null,
            };
        } catch (e) {
            const { message, errors: responseErrors } = e?.response?.data || {};
            errors.value = responseErrors || {};
            alertError("Ooops", message || "Erro ao criar provedor de IA");
        } finally {
            loading.value = false;
        }
    }

    async function handleSave() {
        if( isEditing.value ) {
            await updateAiProvider();
        } else {
            await createAiProvider();
        }
    }

    function open(data = {}) {
        aiProvider.value = data;

        form.value = {
            ...form.value,
            ...data,
            api_key: null,
        };

        showApiKey.value = false;
        isDialogOpen.value = true;
    }

    function reset() {
        errors.value = {};
        showApiKey.value = false;

        aiProvider.value = {};

        form.value = {
            name: null,
            provider: null,
            api_key: null,
            is_enabled: true,
        };
    }

    function handleClose() {
        reset();

        nextTick(() => {
            emit("close");
        });
    }

    reset();

    defineExpose({
        open,
        getAiProvider: () => aiProvider.value,
    });
</script>
