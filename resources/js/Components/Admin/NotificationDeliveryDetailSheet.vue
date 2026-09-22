<template>
    <BottomSheet
        overlay-id="notification-delivery-detail"
        title="Detalhes da Notificação"
        :actions="sheetActions"
        :loading="loading"
        @close="handleClose"
        v-model="isOpen"
    >
        <v-row v-if="delivery.id">
            <v-col cols="12" lg="6">
                <v-text-field
                    label="Canal"
                    :model-value="delivery.channel"
                    readonly
                ></v-text-field>
            </v-col>

            <v-col cols="12" lg="6">
                <v-text-field
                    label="Status"
                    :model-value="statusLabel"
                    readonly
                ></v-text-field>
            </v-col>

            <v-col cols="12">
                <v-text-field
                    label="Assunto"
                    :error-messages="errors.subject"
                    v-model="form.subject"
                ></v-text-field>
            </v-col>

            <v-col cols="12">
                <v-textarea
                    label="Corpo"
                    rows="4"
                    auto-grow
                    :error-messages="errors.body"
                    v-model="form.body"
                ></v-textarea>
            </v-col>

            <v-col cols="12" sm="6" v-if="delivery.sent_at">
                <v-text-field
                    label="Enviado em"
                    :model-value="formatDate(delivery.sent_at, 'dd/MM/yyyy HH:mm')"
                    readonly
                ></v-text-field>
            </v-col>

            <v-col cols="12" sm="6" v-if="delivery.failed_at">
                <v-text-field
                    label="Falhou em"
                    :model-value="formatDate(delivery.failed_at, 'dd/MM/yyyy HH:mm')"
                    readonly
                ></v-text-field>
            </v-col>

            <v-col cols="12" sm="6">
                <v-text-field
                    label="Tentativas"
                    :model-value="delivery.attempts"
                    readonly
                ></v-text-field>
            </v-col>

            <v-col cols="12" v-if="delivery.error_message">
                <v-alert
                    type="error"
                    variant="tonal"
                    title="Erro"
                    :text="delivery.error_message"
                ></v-alert>
            </v-col>

            <v-col cols="12" v-if="delivery.payload">
                <v-textarea
                    label="Payload"
                    rows="4"
                    auto-grow
                    readonly
                    :model-value="formattedPayload"
                ></v-textarea>
            </v-col>
        </v-row>
    </BottomSheet>
</template>

<script setup>
    import { ref, computed } from "vue";

    import api from "@/api";

    import BottomSheet from "@/Components/BottomSheet.vue";

    import { getNotificationDeliveryStatusLabel } from "@/Data/notificationDeliveryStatuses";
    import { formatDate } from "@/Utils/Common";
    import { alertError, toastSuccess } from "@/Utils/Alert";

    const emit = defineEmits([
        "resent",
    ]);

    const isOpen = ref(false);
    const loading = ref(false);
    const resending = ref(false);
    const errors = ref({});

    const delivery = ref({});
    const form = ref({
        subject: "",
        body: "",
    });

    const statusLabel = computed(() => getNotificationDeliveryStatusLabel(delivery.value.status));

    const formattedPayload = computed(() => {
        if( !delivery.value.payload ) return "";
        return JSON.stringify(delivery.value.payload, null, 2);
    });

    const sheetActions = computed(() => [
        {
            key: "resend",
            variant: "tonal",
            color: "primary",
            prependIcon: "mdi-send",
            loading: resending.value,
            text: "Reenviar",
            onClick: handleResend,
        },
    ]);

    async function fetchDelivery(id) {
        loading.value = true;

        try {
            const url = route("api.notification-deliveries.show", id);

            const { data } = await api.get(url);

            delivery.value = data.notification_delivery;

            form.value = {
                subject: delivery.value.subject || "",
                body: delivery.value.body || "",
            };
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao carregar notificação");
            isOpen.value = false;
        } finally {
            loading.value = false;
        }
    }

    async function handleResend() {
        errors.value = {};
        resending.value = true;

        try {
            const url = route("api.notification-deliveries.resend", delivery.value.id);

            await api.post(url, {
                subject: form.value.subject,
                body: form.value.body,
            });

            toastSuccess("Reenvio enfileirado com sucesso");

            emit("resent");

            isOpen.value = false;
        } catch (e) {
            const { message, errors: responseErrors } = e?.response?.data || {};
            errors.value = responseErrors || {};
            alertError("Ooops", message || "Erro ao reenviar notificação");
        } finally {
            resending.value = false;
        }
    }

    function reset() {
        delivery.value = {};

        form.value = {
            subject: "",
            body: "",
        };

        errors.value = {};
    }

    function handleClose() {
        reset();
    }

    async function open(item) {
        reset();
        isOpen.value = true;
        await fetchDelivery(item.id);
    }

    defineExpose({
        open,
    });
</script>
