<template>
    <Dialog
        overlay-id="sgc-customer-attachments"
        title="Anexos"
        max-width="720"
        no-body
        @close="handleClose"
        v-model="isOpen"
    >
        <v-card-text>
            <v-row>
                <v-col cols="12" lg="5">
                    <v-select
                        label="Tipo"
                        :items="typeOptions"
                        item-title="title"
                        item-value="value"
                        :error-messages="errors.type"
                        v-model="form.type"
                    ></v-select>
                </v-col>

                <v-col cols="12" lg="7">
                    <v-file-input
                        label="Arquivo"
                        accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx"
                        prepend-icon="mdi-paperclip"
                        show-size
                        :error-messages="errors.file"
                        v-model="form.file"
                    ></v-file-input>
                </v-col>
            </v-row>
        </v-card-text>

        <v-skeleton-loader
            v-if="loading"
            type="list-item-two-line@4"
        ></v-skeleton-loader>

        <div
            v-else-if="attachments.length === 0"
            class="d-flex align-center justify-center py-8"
        >
            <v-empty-state
                icon="mdi-paperclip"
                title="Nenhum anexo"
                text="Envie um arquivo para vincular a este cliente nesta empresa."
            ></v-empty-state>
        </div>

        <v-list
            v-else
            lines="two"
            style="height: 40dvh; overflow-y: auto;"
        >
            <v-list-item
                v-for="attachment in attachments"
                :key="attachment.id"
                :prepend-icon="typeIcon(attachment.type)"
                :title="attachment.original_name"
                :subtitle="attachmentSubtitle(attachment)"
            >
                <template #append>
                    <v-btn
                        icon="mdi-download"
                        variant="text"
                        :loading="downloadingId === attachment.id"
                        @click="downloadAttachment(attachment)"
                    ></v-btn>

                    <v-btn
                        icon="mdi-delete"
                        variant="text"
                        color="error"
                        @click="confirmRemove(attachment)"
                    ></v-btn>
                </template>
            </v-list-item>
        </v-list>

        <template #actions>
            <v-btn
                prepend-icon="mdi-refresh"
                text="Atualizar"
                :disabled="loading || saving"
                @click="loadAttachments"
            ></v-btn>

            <v-spacer></v-spacer>

            <v-btn
                variant="tonal"
                color="primary"
                prepend-icon="mdi-plus"
                :loading="saving"
                :disabled="loading"
                text="Adicionar"
                @click="handleSave"
            ></v-btn>
        </template>
    </Dialog>

    <Dialog
        overlay-id="sgc-customer-attachment-confirm-remove"
        title="Excluir Anexo"
        max-width="500"
        v-model="isConfirmRemoveDialogOpen"
    >
        O arquivo
        <strong class="text-primary">{{ selectedAttachment?.original_name ?? "" }}</strong>
        será removido permanentemente. Essa ação não pode ser desfeita.

        <template #actions>
            <v-btn
                variant="tonal"
                color="error"
                prepend-icon="mdi-delete"
                :loading="saving"
                text="Excluir"
                @click="handleRemove"
            ></v-btn>
        </template>
    </Dialog>
</template>

<script setup>
    import { reactive, ref } from "vue";

    import api from "@/api";

    import Dialog from "@/Components/Dialog.vue";

    import { alertError, toastSuccess } from "@/Utils/Alert";
    import { formatBytes, formatDate } from "@/Utils/Common";

    const typeOptions = [
        { value: "identity_document", title: "Documento de identidade" },
        { value: "contract", title: "Contrato" },
        { value: "other", title: "Outro" },
    ];

    const typeLabels = Object.fromEntries(
        typeOptions.map(option => [ option.value, option.title ])
    );

    const isOpen = ref(false);
    const isConfirmRemoveDialogOpen = ref(false);
    const loading = ref(false);
    const saving = ref(false);
    const downloadingId = ref(null);
    const errors = ref({});
    const attachments = ref([]);
    const selectedAttachment = ref(null);
    const customerId = ref(null);
    const companyId = ref(null);

    const form = reactive(emptyForm());

    function emptyForm() {
        return {
            type: "other",
            file: null,
        };
    }

    function resetForm() {
        form.type = "other";
        form.file = null;
        errors.value = {};
    }

    function typeIcon(type) {
        return {
            identity_document: "mdi-card-account-details-outline",
            contract: "mdi-file-sign",
            other: "mdi-paperclip",
        }[type] || "mdi-paperclip";
    }

    function attachmentSubtitle(attachment) {
        const parts = [
            typeLabels[attachment.type] || attachment.type,
            formatBytes(attachment.size_bytes),
            formatDate(attachment.created_at, "dd/MM/yyyy HH:mm"),
        ].filter(Boolean);

        return parts.join(" · ");
    }

    function resolveFile(file) {
        if( Array.isArray(file) ) {
            return file[0] || null;
        }

        return file || null;
    }

    async function loadAttachments() {
        if( !customerId.value || companyId.value == null ) return;

        loading.value = true;

        try {
            const url = route("api.tools.sgc.customers.attachments.index", customerId.value);
            const { data } = await api.get(url, {
                params: {
                    company_id: companyId.value,
                },
            });

            attachments.value = data.attachments || [];
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar anexos");
        } finally {
            loading.value = false;
        }
    }

    async function handleSave() {
        if( !customerId.value || companyId.value == null ) return;

        errors.value = {};
        saving.value = true;

        const file = resolveFile(form.file);

        const formData = new FormData();
        formData.append("company_id", companyId.value);
        formData.append("type", form.type || "");

        if( file ) {
            formData.append("file", file);
        }

        try {
            const url = route("api.tools.sgc.customers.attachments.store", customerId.value);

            await api.post(url, formData);

            toastSuccess("Ok", "Anexo adicionado com sucesso");

            resetForm();
            await loadAttachments();
        } catch (e) {
            const { message, errors: responseErrors } = e?.response?.data || {};
            errors.value = responseErrors || {};
            alertError("Ooops", message || "Erro ao adicionar anexo");
        } finally {
            saving.value = false;
        }
    }

    async function downloadAttachment(attachment) {
        downloadingId.value = attachment.id;

        try {
            const url = route("api.tools.sgc.customers.attachments.show", {
                sgc_customer: customerId.value,
                sgc_customer_attachment: attachment.id,
            });

            const { data } = await api.get(url, {
                responseType: "blob",
            });

            const blobUrl = URL.createObjectURL(data);
            const link = document.createElement("a");
            link.href = blobUrl;
            link.download = attachment.original_name;
            link.click();
            URL.revokeObjectURL(blobUrl);
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao baixar anexo");
        } finally {
            downloadingId.value = null;
        }
    }

    function confirmRemove(attachment) {
        selectedAttachment.value = attachment;
        isConfirmRemoveDialogOpen.value = true;
    }

    async function handleRemove() {
        if( !customerId.value || !selectedAttachment.value?.id ) return;

        saving.value = true;

        try {
            const url = route("api.tools.sgc.customers.attachments.destroy", {
                sgc_customer: customerId.value,
                sgc_customer_attachment: selectedAttachment.value.id,
            });

            await api.delete(url);

            toastSuccess("Ok", "Anexo removido com sucesso");

            isConfirmRemoveDialogOpen.value = false;
            selectedAttachment.value = null;

            await loadAttachments();
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao remover anexo");
        } finally {
            saving.value = false;
        }
    }

    function open(id, idCompany = null) {
        customerId.value = id;
        companyId.value = idCompany;
        resetForm();
        attachments.value = [];
        isOpen.value = true;

        loadAttachments();
    }

    function close() {
        isOpen.value = false;
        handleClose();
    }

    function handleClose() {
        isConfirmRemoveDialogOpen.value = false;
        selectedAttachment.value = null;
        customerId.value = null;
        companyId.value = null;
        attachments.value = [];
        resetForm();
    }

    defineExpose({
        open,
        close,
    });
</script>
