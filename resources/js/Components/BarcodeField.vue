<template>
    <v-text-field
        v-bind="$attrs"
        v-model="model"
    >
        <template #append-inner>
            <v-btn
                size="small"
                icon="mdi-qrcode-scan"
                variant="tonal"
                color="primary"
                class="rounded"
                :disabled="isDisabled"
                @click.prevent="openScanner"
            ></v-btn>
        </template>
    </v-text-field>

    <Dialog
        overlay-id="barcode-scanner"
        title="Ler código"
        max-width="480px"
        @close="handleClose"
        v-model="isOpen"
    >
        <p class="text-body-2 text-medium-emphasis mb-4">
            Aponte a câmera para o QR code ou código de barras.
        </p>

        <v-alert
            v-if="errorMessage"
            type="error"
            variant="tonal"
            class="mb-4"
        >
            <div>{{ errorMessage }}</div>

            <v-btn
                class="mt-2"
                size="small"
                variant="tonal"
                text="Tentar novamente"
                @click="retry"
            ></v-btn>
        </v-alert>

        <div
            v-else
            class="barcode-field__preview rounded overflow-hidden"
        >
            <QrcodeStream
                v-if="isOpen"
                :key="scannerKey"
                :formats="formats"
                :paused="paused"
                :track="paintBoundingBox"
                @detect="onDetect"
                @camera-on="onCameraOn"
                @error="onError"
            >
                <div
                    v-if="cameraLoading"
                    class="d-flex align-center justify-center fill-height"
                >
                    <v-progress-circular
                        indeterminate
                        color="primary"
                    ></v-progress-circular>
                </div>
            </QrcodeStream>
        </div>
    </Dialog>
</template>

<script setup>
    import { computed, ref, useAttrs } from "vue";
    import { QrcodeStream } from "vue-qrcode-reader";

    import Dialog from "@/Components/Dialog.vue";

    defineOptions({ inheritAttrs: false });

    const attrs = useAttrs();

    defineProps({
        formats: {
            type: Array,
            default: () => [
                "qr_code",
                "ean_13",
                "ean_8",
                "upc_a",
                "upc_e",
                "code_128",
                "code_39",
                "codabar",
                "itf",
            ],
        },
    });

    const model = defineModel({
        type: [ String, null ],
        default: null,
    });

    const isOpen = ref(false);
    const paused = ref(false);
    const cameraLoading = ref(false);
    const errorMessage = ref(null);
    const scannerKey = ref(0);

    const isDisabled = computed(() => {
        const value = attrs.disabled ?? attrs.readonly;

        return value === "" || value === true || value === "disabled" || value === "readonly";
    });

    function paintBoundingBox(detectedCodes, ctx) {
        for( const detectedCode of detectedCodes ) {
            const { x, y, width, height } = detectedCode.boundingBox;

            ctx.lineWidth = 3;
            ctx.strokeStyle = "#4caf50";
            ctx.strokeRect(x, y, width, height);
        }
    }

    function openScanner() {
        if( isDisabled.value ) {
            return;
        }

        errorMessage.value = null;
        paused.value = false;
        cameraLoading.value = true;
        isOpen.value = true;
    }

    function onCameraOn() {
        cameraLoading.value = false;
    }

    function onDetect(detectedCodes) {
        const value = detectedCodes?.[0]?.rawValue;

        if( !value ) {
            return;
        }

        paused.value = true;
        model.value = value;
        isOpen.value = false;
    }

    function onError(error) {
        cameraLoading.value = false;
        errorMessage.value = getErrorMessage(error);
    }

    function getErrorMessage(error) {
        switch( error?.name ) {
            case "NotAllowedError":
                return "Permissão da câmera negada. Libere o acesso nas configurações do navegador.";
            case "NotFoundError":
                return "Nenhuma câmera foi encontrada neste dispositivo.";
            case "NotSupportedError":
                return "A câmera só funciona em conexões seguras (HTTPS).";
            case "NotReadableError":
                return "A câmera já está em uso por outro aplicativo.";
            case "OverconstrainedError":
                return "Não foi possível acessar a câmera traseira deste dispositivo.";
            case "StreamApiNotSupportedError":
                return "Seu navegador não suporta acesso à câmera.";
            default:
                return error?.message || "Não foi possível iniciar a câmera.";
        }
    }

    function retry() {
        errorMessage.value = null;
        cameraLoading.value = true;
        scannerKey.value += 1;
    }

    function handleClose() {
        paused.value = false;
        cameraLoading.value = false;
        errorMessage.value = null;
    }
</script>

<style scoped>
    .barcode-field__preview {
        position: relative;
        width: 100%;
        aspect-ratio: 4 / 3;
        background: #111;
    }

    .barcode-field__preview :deep(> div) {
        height: 100%;
    }
</style>
