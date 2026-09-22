<template>
    <BottomSheet
        overlay-id="notification-provider-form"
        :title="isEditing ? 'Editar Provedor de Notificação' : 'Novo Provedor de Notificação'"
        :actions="sheetActions"
        @close="handleClose"
        v-model="isDialogOpen"
    >
        <v-row>
            <v-col cols="12" lg="6" xl="4">
                <v-text-field
                    label="Nome"
                    :error-messages="errors.name"
                    v-model="form.name"
                ></v-text-field>
            </v-col>

            <v-col cols="12" lg="6" xl="4">
                <v-select
                    label="Canal"
                    item-title="title"
                    item-value="value"
                    :items="notificationProviderChannelOptions"
                    :error-messages="errors.channel"
                    :disabled="isEditing"
                    v-model="form.channel"
                ></v-select>
            </v-col>

            <v-col cols="12" lg="6" xl="4">
                <v-text-field
                    label="Chave"
                    hint="Geralmente o slug da categoria do post"
                    persistent-hint
                    :error-messages="errors.key"
                    :disabled="isEditing"
                    v-model="form.key"
                ></v-text-field>
            </v-col>

            <template
                v-for="field in configFields"
                :key="field.key"
            >
                <v-col cols="12">
                    <v-textarea
                        v-if="field.type === 'textarea'"
                        :label="field.label"
                        :hint="configFieldHint(field)"
                        :persistent-hint="shouldPersistConfigHint(field)"
                        :error-messages="errors[`config.${field.key}`]"
                        rows="3"
                        auto-grow
                        v-model="form.config[field.key]"
                    ></v-textarea>

                    <v-text-field
                        v-else
                        :label="field.label"
                        :type="configFieldInputType(field)"
                        :hint="configFieldHint(field)"
                        :persistent-hint="shouldPersistConfigHint(field)"
                        :error-messages="errors[`config.${field.key}`]"
                        :append-inner-icon="field.type === 'secret' ? secretToggleIcon(field.key) : undefined"
                        @click:append-inner="toggleSecretVisibility(field.key)"
                        v-model="form.config[field.key]"
                    ></v-text-field>
                </v-col>
            </template>

            <v-col cols="12">
                <v-switch
                    label="Ativo"
                    color="primary"
                    :error-messages="errors.is_enabled"
                    v-model="form.is_enabled"
                ></v-switch>
            </v-col>
        </v-row>
    </BottomSheet>
</template>

<script setup>
    import { ref, computed, watch, nextTick } from "vue";

    import api from "@/api";

    import BottomSheet from "@/Components/BottomSheet.vue";

    import {
        getDefaultNotificationProviderChannel,
        getNotificationProviderChannel,
        notificationProviderChannelOptions,
        buildEmptyProviderConfig,
        buildProviderConfigPayload,
    } from "@/Data/notificationProviderChannels";

    import { alertError, toastSuccess } from "@/Utils/Alert";

    const emit = defineEmits([
        "close",
    ]);

    const isDialogOpen = ref(false);
    const visibleSecrets = ref({});
    const loading = ref(false);
    const errors = ref({});

    const notificationProvider = ref({});
    const form = ref({});

    const isEditing = computed(() => !!notificationProvider.value.id);

    const configFields = computed(() => {
        return getNotificationProviderChannel(form.value.channel)?.configFields || [];
    });

    const sheetActions = computed(() => [
        {
            key: "save",
            variant: "tonal",
            color: "primary",
            prependIcon: "mdi-content-save",
            loading: loading.value,
            text: "Salvar",
            onClick: handleSave,
        },
    ]);

    function configFieldHint(field) {
        if( isEditing.value && field.keepOnEdit ) {
            return "Deixe em branco para manter o valor atual";
        }

        return field.hint || "";
    }

    function shouldPersistConfigHint(field) {
        return isEditing.value && !!field.keepOnEdit;
    }

    function configFieldInputType(field) {
        if( field.type !== "secret" ) {
            return field.type || "text";
        }

        return visibleSecrets.value[field.key] ? "text" : "password";
    }

    function secretToggleIcon(key) {
        return visibleSecrets.value[key] ? "mdi-eye-off" : "mdi-eye";
    }

    function toggleSecretVisibility(key) {
        visibleSecrets.value[key] = !visibleSecrets.value[key];
    }

    function resetConfigForChannel(channel) {
        form.value.config = buildEmptyProviderConfig(channel);
        visibleSecrets.value = {};
    }

    function buildPayload() {
        const payload = {
            name: form.value.name,
            channel: form.value.channel,
            key: form.value.key,
            is_enabled: form.value.is_enabled,
        };

        const config = buildProviderConfigPayload(form.value.channel, form.value.config);

        if( Object.keys(config).length > 0 || !isEditing.value ) {
            payload.config = config;
        }

        return payload;
    }

    async function handleSave() {
        errors.value = {};
        loading.value = true;

        try {
            const url = isEditing.value
                ? route("api.notification-providers.update", notificationProvider.value.id)
                : route("api.notification-providers.store");

            const { data } = await api[isEditing.value ? "put" : "post"](url, buildPayload());

            toastSuccess(
                isEditing.value
                    ? "Provedor de notificação atualizado com sucesso"
                    : "Provedor de notificação criado com sucesso",
            );

            notificationProvider.value = data.notification_provider;

            resetConfigForChannel(form.value.channel);
        } catch (e) {
            const { message, errors: responseErrors } = e?.response?.data || {};

            errors.value = responseErrors || {};

            alertError(
                "Ooops",
                message || `Erro ao ${isEditing.value ? "atualizar" : "criar"} provedor de notificação`,
            );
        } finally {
            loading.value = false;
        }
    }

    function open(data = {}) {
        notificationProvider.value = data;

        const channel = data.channel || getDefaultNotificationProviderChannel();

        form.value = {
            name: data.name ?? null,
            channel,
            key: data.key ?? null,
            is_enabled: data.is_enabled ?? true,
            config: buildEmptyProviderConfig(channel),
        };

        visibleSecrets.value = {};

        isDialogOpen.value = true;
    }

    function reset() {
        errors.value = {};
        visibleSecrets.value = {};
        notificationProvider.value = {};

        const channel = getDefaultNotificationProviderChannel();

        form.value = {
            name: null,
            channel,
            key: null,
            is_enabled: true,
            config: buildEmptyProviderConfig(channel),
        };
    }

    function handleClose() {
        reset();

        nextTick(() => {
            emit("close");
        });
    }

    watch(() => form.value.channel, (channel, previousChannel) => {
        if( !previousChannel || channel === previousChannel || isEditing.value ) {
            return;
        }

        resetConfigForChannel(channel);
    });

    reset();

    defineExpose({
        open,
    });
</script>
