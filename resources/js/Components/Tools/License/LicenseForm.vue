<template>
    <BottomSheet
        overlay-id="license-form"
        :title="isEditing ? 'Editar Licença' : 'Nova Licença'"
        :loading="loading || deleting"
        :actions="sheetActions"
        @close="handleClose"
        v-model="isDialogOpen"
    >
        <v-row>
            <v-col cols="12" lg="6">
                <v-text-field
                    label="Nome"
                    :error-messages="errors.label"
                    v-model="form.label"
                ></v-text-field>
            </v-col>

            <v-col cols="12" lg="6" xl="3">
                <DateField
                    label="Expira em"
                    :error-messages="errors.expires_at"
                    v-model="form.expires_at"
                ></DateField>
            </v-col>

            <v-col cols="12" lg="6" xl="3">
                <v-select
                    label="Status"
                    :items="statusOptions"
                    item-title="label"
                    item-value="value"
                    :error-messages="errors.status"
                    :disabled="!isEditing"
                    v-model="form.status"
                ></v-select>
            </v-col>

            <v-col cols="12">
                <v-textarea
                    label="Observações"
                    auto-grow
                    :error-messages="errors.observations"
                    v-model="form.observations"
                ></v-textarea>
            </v-col>

            <v-col v-if="!isEditing" cols="12">
                <v-card
                    :color="quotaAlertType"
                    prepend-icon="mdi-key-chain-variant"
                >
                    <template #title>
                        {{ usedLicenses }}/{{ maxLicenses }} Licenças no Plano
                    </template>

                    <v-divider></v-divider>

                    <v-card-text>
                        <div>
                            Você pode ter até {{ maxLicenses }} licenças no plano ao mesmo tempo.
                        </div>

                        <ul>
                            <li><strong>Ocupam vaga:</strong> Pendente, Ativa e Suspensa.</li>
                            <li><strong>Não ocupa vaga:</strong> Revogada — fica no histórico, mas libera o slot para criar outra.</li>
                        </ul>

                        <div>
                            Ao atingir o limite, revogue ou remova uma licença existente antes de criar uma nova.
                        </div>

                        <div>
                            Em breve, planos pagos com limites maiores estarão disponíveis.
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </BottomSheet>

    <LicenseHelpDialog v-model="isHelpOpen"></LicenseHelpDialog>

    <LicenseTokenDialog ref="tokenDialogRef"></LicenseTokenDialog>
</template>

<script setup>
    import { ref, computed, nextTick } from "vue";

    import api from "@/api";

    import BottomSheet from "@/Components/BottomSheet.vue";
    import DateField from "@/Components/DateField.vue";
    import LicenseHelpDialog from "@/Components/Tools/License/LicenseHelpDialog.vue";
    import LicenseTokenDialog from "@/Components/Tools/License/LicenseTokenDialog.vue";

    import { alertError, confirm, confirmDelete, toastSuccess } from "@/Utils/Alert";

    const props = defineProps({
        maxLicenses: {
            type: Number,
            default: 0,
        },
        usedLicenses: {
            type: Number,
            default: 0,
        },
    });

    const quotaAlertType = computed(() => {
        if( props.usedLicenses >= props.maxLicenses ) return "error";
        if( props.usedLicenses >= props.maxLicenses - 1 ) return "warning";
        return "info";
    });

    const emit = defineEmits([
        "form-closed",
        "saved",
    ]);

    const isDialogOpen = ref(false);
    const isHelpOpen = ref(false);
    const tokenDialogRef = ref(null);
    const loading = ref(false);
    const deleting = ref(false);
    const errors = ref({});

    const license = ref({});
    const form = ref({});

    const statusOptions = [
        { value: "pending", label: "Pendente" },
        { value: "active", label: "Ativa" },
        { value: "suspended", label: "Suspensa" },
        { value: "revoked", label: "Revogada" },
    ];

    const isEditing = computed(() => !!license.value.id);

    const sheetActions = computed(() => {
        const actions = [];

        if( isEditing.value ) {
            actions.push({
                key: "delete",
                color: "error",
                variant: "tonal",
                prependIcon: "mdi-delete",
                loading: deleting.value,
                text: "Remover",
                onClick: handleDelete,
            });
        }

        actions.push({ key: "spacer", spacer: true });

        if( isEditing.value && license.value.status === "pending" ) {
            actions.push({
                key: "reissue",
                variant: "tonal",
                color: "secondary",
                prependIcon: "mdi-refresh",
                text: "Reemitir Token",
                onClick: handleReissueToken,
            });
        }

        actions.push({
            key: "help",
            variant: "tonal",
            color: "info",
            prependIcon: "mdi-help-circle-outline",
            text: "Como Integrar",
            onClick: () => {
                isHelpOpen.value = true;
            },
        });

        actions.push({
            key: "save",
            variant: "tonal",
            color: "primary",
            prependIcon: "mdi-content-save",
            loading: loading.value,
            text: "Salvar",
            onClick: handleSave,
        });

        return actions;
    });

    function buildPayload() {
        return {
            label: form.value.label,
            observations: form.value.observations,
            expires_at: form.value.expires_at,
        };
    }

    async function createLicense() {
        if( loading.value ) return;

        errors.value = {};
        loading.value = true;

        try {
            const url = route("api.tools.licenses.store");

            const { data } = await api.post(url, buildPayload());

            toastSuccess("Licença criada com sucesso");

            license.value = data.license;

            form.value = {
                ...data.license,
            };

            tokenDialogRef.value?.open({
                licenseId: data.license.id,
            });

            emit("saved");
        } catch (e) {
            const { message, errors: responseErrors } = e?.response?.data || {};
            errors.value = responseErrors || {};
            alertError("Ooops", message || "Erro ao criar licença");
        } finally {
            loading.value = false;
        }
    }

    async function updateLicense() {
        errors.value = {};
        loading.value = true;

        try {
            const url = route("api.tools.licenses.update", license.value.id);

            const { data } = await api.put(url, buildPayload());

            if( form.value.status !== license.value.status ) {
                const statusUrl = route("api.tools.licenses.status", license.value.id);

                const statusResponse = await api.put(statusUrl, {
                    status: form.value.status,
                });

                license.value = statusResponse.data.license;
            } else {
                license.value = data.license;
            }

            toastSuccess("Licença atualizada com sucesso");

            form.value = {
                ...form.value,
                ...license.value,
            };
        } catch (e) {
            const { message, errors: responseErrors } = e?.response?.data || {};
            errors.value = responseErrors || {};
            alertError("Ooops", message || "Erro ao atualizar licença");
        } finally {
            loading.value = false;
        }
    }

    async function handleReissueToken() {
        const { isConfirmed } = await confirm(
            "Reemitir Token",
            "Um novo token de ativação será gerado e o token anterior deixará de funcionar imediatamente. Dispositivos que ainda não ativaram a licença precisarão usar o novo token.",
        );

        if( !isConfirmed ) return;

        tokenDialogRef.value?.open({
            licenseId: license.value.id,
            successMessage: "Token reemitido com sucesso",
        });
    }

    async function handleDelete() {
        const name = form.value.label || "";

        const { isConfirmed } = await confirmDelete(
            "Excluir Licença",
            `A licença "${name}" será removida permanentemente. Essa ação não pode ser desfeita.`,
        );

        if( !isConfirmed ) return;

        deleting.value = true;

        try {
            const url = route("api.tools.licenses.destroy", license.value.id);

            await api.delete(url);

            toastSuccess("Licença removida com sucesso");

            isDialogOpen.value = false;

            handleClose();
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao remover licença");
        } finally {
            deleting.value = false;
        }
    }

    async function handleSave() {
        if( loading.value ) return;

        if( isEditing.value ) {
            await updateLicense();
        } else {
            await createLicense();
        }
    }

    async function open(data = {}) {
        license.value = data;

        form.value = {
            ...form.value,
            ...data,
        };

        isDialogOpen.value = true;
    }

    function reset() {
        errors.value = {};
        license.value = {};

        form.value = {
            label: null,
            observations: null,
            expires_at: null,
            status: "pending",
        };
    }

    function handleClose() {
        reset();

        nextTick(() => {
            emit("form-closed");
        });
    }

    reset();

    defineExpose({
        open,
    });
</script>
