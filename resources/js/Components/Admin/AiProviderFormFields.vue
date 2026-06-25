<template>
    <v-bottom-sheet
        inset
        scrollable
        transition="dialog-bottom-transition"
        @after-leave="handleClose"
        v-model="isDialogOpen"
    >
        <v-card
            height="70dvh"
            min-height="400px"
            class="rounded-b-0"
        >
            <v-toolbar>
                <v-toolbar-title>
                    {{ isEditing ? "Editar Provedor de IA" : "Novo Provedor de IA" }}
                </v-toolbar-title>

                <v-btn
                    icon="mdi-close"
                    variant="tonal"
                    color="primary"
                    @click="isDialogOpen = false"
                ></v-btn>
            </v-toolbar>

            <v-card-text>
                <v-row>
                    <v-col cols="12" lg="6" xl="6">
                        <v-text-field
                            label="Nome"
                            :error-messages="errors.name"
                            v-model="form.name"
                        ></v-text-field>
                    </v-col>

                    <v-col cols="12" lg="6" xl="6">
                        <v-text-field
                            label="Provedor"
                            hint="Identificador do provedor (ex: openai, anthropic)"
                            persistent-hint
                            :error-messages="errors.provider"
                            v-model="form.provider"
                        ></v-text-field>
                    </v-col>

                    <v-col cols="12">
                        <v-text-field
                            label="URL"
                            :error-messages="errors.url"
                            v-model="form.url"
                        ></v-text-field>
                    </v-col>

                    <v-col cols="12" lg="9" xl="10">
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

                    <v-col cols="12" lg="3" xl="2">
                        <v-switch
                            label="Ativo"
                            color="primary"
                            :error-messages="errors.is_enabled"
                            v-model="form.is_enabled"
                        ></v-switch>
                    </v-col>
                </v-row>
            </v-card-text>

            <v-divider></v-divider>

            <v-card-actions>
                <slot
                    v-if="isEditing"
                    name="action-buttons"
                ></slot>

                <v-btn
                    variant="tonal"
                    color="primary"
                    prepend-icon="mdi-content-save"
                    :loading="loading"
                    @click="handleSave"
                >Salvar</v-btn>
            </v-card-actions>
        </v-card>
    </v-bottom-sheet>
</template>

<script setup>
    import { ref, computed, nextTick } from "vue";

    import api from "@/api";

    import { alertError, alertSuccess } from "@/Utils/Alert";

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
            url: form.value.url,
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

            alertSuccess("Ok", "Provedor de IA atualizado com sucesso");

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

            alertSuccess("Ok", "Provedor de IA criado com sucesso");

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
        document.documentElement.style.overflowY = "hidden";

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
            url: null,
            api_key: null,
            is_enabled: true,
        };
    }

    function handleClose() {
        reset();

        document.documentElement.style.overflowY = "scroll";

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
