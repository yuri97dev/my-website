<template>
    <div>
        <input
            ref="fileInputRef"
            type="file"
            accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx"
            multiple
            class="d-none"
            @change="handleFileSelect"
        />

        <v-skeleton-loader
            v-if="loading"
            type="list-item-two-line@4"
        ></v-skeleton-loader>

        <v-card
            v-else-if="attachments.length === 0"
            border
            variant="tonal"
            class="text-center pa-8"
        >
            <v-icon
                icon="mdi-paperclip"
                size="48"
                color="primary"
                class="mb-4"
            ></v-icon>

            <div class="text-subtitle-1 font-weight-medium mb-1">
                Nenhum anexo
            </div>

            <div class="text-body-2 text-medium-emphasis">
                Use “Adicionar Arquivo” no rodapé.
            </div>
        </v-card>

        <v-list
            v-else
            lines="two"
            class="py-0"
            style="max-height: 40dvh; overflow-y: auto;"
        >
            <v-list-item
                v-for="attachment in attachments"
                :key="attachment.id"
                :prepend-icon="typeIcon(attachment.type)"
                :title="attachment.original_name"
                :subtitle="attachmentSubtitle(attachment)"
                class="rounded-lg"
                @click="openAttachment(attachment)"
            >
                <template #append>
                    <v-menu offset="8" location="bottom end">
                        <template #activator="{ props }">
                            <v-btn
                                icon
                                variant="tonal"
                                color="primary"
                                class="rounded"
                                v-bind="props"
                                @click.stop
                            >
                                <v-progress-circular
                                    v-if="saving"
                                    color="primary"
                                    indeterminate
                                ></v-progress-circular>

                                <v-icon
                                    v-else
                                    icon="mdi-dots-vertical"
                                ></v-icon>
                            </v-btn>
                        </template>

                        <v-list
                            v-if="!saving"
                            slim
                            border
                        >
                            <v-list-item
                                prepend-icon="mdi-tag-outline"
                                title="Alterar Tipo"
                                @click="openTypeDialog(attachment)"
                            ></v-list-item>

                            <v-list-item
                                prepend-icon="mdi-delete"
                                title="Excluir"
                                @click="confirmRemove(attachment)"
                            ></v-list-item>
                        </v-list>
                    </v-menu>
                </template>
            </v-list-item>
        </v-list>
    </div>

    <Dialog
        overlay-id="sgc-customer-attachment-type"
        title="Tipo do anexo"
        max-width="400"
        :loading="savingType"
        @close="resetTypeForm"
        v-model="isTypeDialogOpen"
    >
        <v-select
            label="Tipo"
            :items="typeOptions"
            item-title="title"
            item-value="value"
            :error-messages="typeErrors.type"
            v-model="typeForm.type"
        ></v-select>

        <template #actions>
            <v-btn
                variant="tonal"
                color="primary"
                prepend-icon="mdi-content-save"
                text="Salvar"
                :loading="savingType"
                @click="handleSaveType"
            ></v-btn>
        </template>
    </Dialog>

    <ImageViewerDialog
        ref="imageViewerDialogRef"
    ></ImageViewerDialog>
</template>

<script setup>
    import { reactive, ref, watch } from "vue";

    import api from "@/api";

    import Dialog from "@/Components/Dialog.vue";
    import ImageViewerDialog from "@/Components/ImageViewerDialog.vue";

    import { alertError, confirmDelete, toastSuccess } from "@/Utils/Alert";
    import { formatBytes, formatDate } from "@/Utils/Common";
    import { compressImageFile, isCompressibleImage } from "@/Utils/ImageCompression";

    const props = defineProps({
        customerId: {
            type: [ Number, null ],
            default: null,
        },
        companyId: {
            type: [ Number, null ],
            default: null,
        },
    });

    const typeOptions = [
        { value: "identity_document", title: "Documento de Identidade" },
        { value: "contract", title: "Contrato" },
        { value: "other", title: "Outro" },
    ];

    const imageViewerDialogRef = ref(null);
    const fileInputRef = ref(null);
    const loading = ref(false);
    const saving = ref(false);
    const savingType = ref(false);
    const isTypeDialogOpen = ref(false);
    const errors = ref({});
    const typeErrors = ref({});
    const attachments = ref([]);
    const selectedAttachment = ref(null);

    const typeForm = reactive({
        attachment: null,
        type: "other",
    });

    function typeLabel(type) {
        return typeOptions.find(option => option.value === type)?.title || type;
    }

    function openTypeDialog(attachment) {
        typeForm.attachment = attachment;
        typeForm.type = attachment.type;
        typeErrors.value = {};
        isTypeDialogOpen.value = true;
    }

    function resetTypeForm() {
        typeForm.attachment = null;
        typeForm.type = "other";
        typeErrors.value = {};
    }

    async function handleSaveType() {
        if( !typeForm.attachment ) return;

        const saved = await updateType(typeForm.attachment, typeForm.type);

        if( saved ) {
            isTypeDialogOpen.value = false;
        }
    }

    function pickFile() {
        fileInputRef.value?.click();
    }

    async function handleFileSelect(event) {
        const files = Array.from(event.target.files || []);

        event.target.value = "";

        for( const file of files ) {
            await uploadFile(file);
        }
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
            typeLabel(attachment.type),
            formatBytes(attachment.size_bytes),
            formatDate(attachment.created_at, "dd/MM/yyyy HH:mm"),
        ].filter(Boolean);

        return parts.join(" · ");
    }

    async function loadAttachments() {
        if( !props.customerId || props.companyId == null ) return;

        loading.value = true;

        try {
            const url = route("api.tools.sgc.customers.attachments.index", props.customerId);

            const { data } = await api.get(url, {
                params: {
                    company_id: props.companyId,
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

    async function uploadFile(file) {
        if( !props.customerId || props.companyId == null || !file ) return;

        errors.value = {};
        saving.value = true;

        const preparedFile = isCompressibleImage(file)
            ? await compressImageFile(file)
            : file;

        const formData = new FormData();

        formData.append("company_id", props.companyId);
        formData.append("file", preparedFile);

        try {
            const url = route("api.tools.sgc.customers.attachments.store", props.customerId);

            await api.post(url, formData);

            toastSuccess("Ok", "Anexo adicionado com sucesso");

            await loadAttachments();
        } catch (e) {
            const { message, errors: responseErrors } = e?.response?.data || {};
            errors.value = responseErrors || {};
            alertError("Ooops", message || "Erro ao adicionar anexo");
        } finally {
            saving.value = false;
        }
    }

    async function updateType(attachment, type) {
        if( !props.customerId || !attachment?.id || type === attachment.type ) {
            return true;
        }

        savingType.value = true;
        typeErrors.value = {};

        try {
            const url = route("api.tools.sgc.customers.attachments.update", {
                sgc_customer: props.customerId,
                sgc_customer_attachment: attachment.id,
            });

            await api.put(url, { type });

            toastSuccess("Ok", "Tipo do anexo atualizado");

            await loadAttachments();

            return true;
        } catch (e) {
            const { message, errors: responseErrors } = e?.response?.data || {};

            typeErrors.value = responseErrors || {};
            alertError("Ooops", message || "Erro ao atualizar tipo do anexo");

            return false;
        } finally {
            savingType.value = false;
        }
    }

    function isImageAttachment(attachment) {
        if( typeof attachment.mime_type === "string" && attachment.mime_type.startsWith("image/") ) {
            return true;
        }

        return /\.(jpe?g|png|webp)$/i.test(attachment.original_name || "");
    }

    function openAttachment(attachment) {
        const url = route("tools.sgc.customers.attachments.show", {
            sgc_customer: props.customerId,
            sgc_customer_attachment: attachment.id,
        });

        if( isImageAttachment(attachment) ) {
            imageViewerDialogRef.value.open(url);
            return;
        }

        window.open(url, "_blank", "noopener,noreferrer");
    }

    async function confirmRemove(attachment) {
        const { isConfirmed } = await confirmDelete(
            "Excluir Anexo",
            `O arquivo "${attachment.original_name}" será removido permanentemente. Essa ação não pode ser desfeita.`,
        );

        if( !isConfirmed ) return;

        selectedAttachment.value = attachment;
        await handleRemove();
    }

    async function handleRemove() {
        if( !props.customerId || !selectedAttachment.value?.id ) return;

        saving.value = true;

        try {
            const url = route("api.tools.sgc.customers.attachments.destroy", {
                sgc_customer: props.customerId,
                sgc_customer_attachment: selectedAttachment.value.id,
            });

            await api.delete(url);

            toastSuccess("Ok", "Anexo removido com sucesso");

            selectedAttachment.value = null;

            await loadAttachments();
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao remover anexo");
        } finally {
            saving.value = false;
        }
    }

    function reset() {
        selectedAttachment.value = null;
        attachments.value = [];
        errors.value = {};
    }

    watch(
        () => [ props.customerId, props.companyId ],
        ([ customerId, companyId ]) => {
            if( !customerId || companyId == null ) {
                reset();
                return;
            }

            loadAttachments();
        },
        { immediate: true },
    );

    defineExpose({
        load: loadAttachments,
        pickFile,
        reset,
        loading,
        saving,
    });
</script>
