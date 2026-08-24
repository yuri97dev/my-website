<template>
    <div>
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
            style="max-height: 40dvh; overflow-y: auto;"
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
                        icon="mdi-open-in-new"
                        variant="text"
                        @click="openAttachment(attachment)"
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
    </div>

    <ImageViewerDialog
        ref="imageViewerDialogRef"
    ></ImageViewerDialog>
</template>

<script setup>
    import { reactive, ref, watch } from "vue";

    import api from "@/api";

    import ImageViewerDialog from "@/Components/ImageViewerDialog.vue";

    import { alertError, confirmDelete, toastSuccess } from "@/Utils/Alert";
    import { formatBytes, formatDate } from "@/Utils/Common";

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
        { value: "identity_document", title: "Documento de identidade" },
        { value: "contract", title: "Contrato" },
        { value: "other", title: "Outro" },
    ];

    const typeLabels = Object.fromEntries(
        typeOptions.map(option => [ option.value, option.title ])
    );

    const imageViewerDialogRef = ref(null);
    const loading = ref(false);
    const saving = ref(false);
    const errors = ref({});
    const attachments = ref([]);
    const selectedAttachment = ref(null);

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

    async function handleSave() {
        if( !props.customerId || props.companyId == null ) return;

        errors.value = {};
        saving.value = true;

        const file = resolveFile(form.file);

        const formData = new FormData();
        formData.append("company_id", props.companyId);
        formData.append("type", form.type || "");

        if( file ) {
            formData.append("file", file);
        }

        try {
            const url = route("api.tools.sgc.customers.attachments.store", props.customerId);

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
        resetForm();
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
        save: handleSave,
        reset,
        loading,
        saving,
    });
</script>
