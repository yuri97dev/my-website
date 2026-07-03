<template>
    <Dialog
        title="Recortar Imagem"
        no-body
        max-width="900px"
        @close="handleClose"
        v-model="isOpen"
    >
        <v-card-text>
            <ImageCropper
                ref="cropperRef"
                :src="src"
                :output-width="outputWidth"
                :output-height="outputHeight"
                :mime-type="mimeType"
                :quality="quality"
                @error="handleError"
            ></ImageCropper>
        </v-card-text>

        <template #actions>
            <v-btn
                variant="tonal"
                color="primary"
                prepend-icon="mdi-content-cut"
                text="Recortar"
                :loading="loading"
                @click="confirm"
            ></v-btn>
        </template>
    </Dialog>
</template>

<script setup>
    import { ref, nextTick } from "vue";

    import Dialog from "@/Components/Dialog.vue";
    import ImageCropper from "@/Components/ImageCropper.vue";

    import { alertError } from "@/Utils/Alert";

    const props = defineProps({
        outputWidth: {
            type: Number,
            default: undefined,
        },
        outputHeight: {
            type: Number,
            default: undefined,
        },
        mimeType: {
            type: String,
            default: "image/jpeg",
        },
        quality: {
            type: Number,
            default: 0.92,
        },
    });

    const emit = defineEmits([
        "confirm",
    ]);

    const isOpen = ref(false);
    const src = ref(null);
    const loading = ref(false);
    const cropperRef = ref(null);

    let sourceFile = null;

    function open(input) {
        sourceFile = input instanceof File ? input : null;
        src.value = input;
        isOpen.value = true;
    }

    async function confirm() {
        loading.value = true;

        try {
            const filename = sourceFile?.name || "imagem-recortada.jpg";
            const file = await cropperRef.value.cropAsFile(filename);

            emit("confirm", file);

            isOpen.value = false;
        } catch (e) {
            alertError("Ooops", e?.message || "Erro ao recortar imagem");
        } finally {
            loading.value = false;
        }
    }

    function handleError() {
        alertError("Ooops", "Erro ao carregar imagem para recorte");
    }

    function handleClose() {
        src.value = null;
        sourceFile = null;

        nextTick(() => {
            loading.value = false;
        });
    }

    defineExpose({
        open,
    });
</script>
