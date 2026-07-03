<template>
    <div class="image-cropper">
        <div class="d-flex justify-center pa-2">
            <v-btn-toggle
                mandatory
                divided
                density="compact"
                v-model="isSquare"
            >
                <v-btn
                    :value="false"
                    text="Livre"
                ></v-btn>

                <v-btn
                    :value="true"
                    text="Quadrado"
                ></v-btn>
            </v-btn-toggle>
        </div>

        <div
            ref="containerRef"
            class="image-cropper__container"
            :style="{ height }"
        ></div>
    </div>
</template>

<script setup>
    import { ref, watch, onMounted, onBeforeUnmount, nextTick } from "vue";

    import Cropper from "cropperjs";

    const props = defineProps({
        src: {
            type: [String, File, Blob],
            default: null,
        },
        height: {
            type: String,
            default: "360px",
        },
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
        "error",
    ]);

    const containerRef = ref(null);
    const isSquare = ref(false);

    let cropper = null;
    let objectUrl = null;

    function resolveSrc(src) {
        if( !src ) return null;

        if( src instanceof File || src instanceof Blob ) {
            return URL.createObjectURL(src);
        }

        return src;
    }

    function revokeObjectUrl() {
        if( objectUrl ) {
            URL.revokeObjectURL(objectUrl);
            objectUrl = null;
        }
    }

    function destroyCropper() {
        cropper?.destroy();
        cropper = null;
        revokeObjectUrl();
    }

    function applyAspectRatio() {
        const selection = cropper?.getCropperSelection();

        if( !selection ) return;

        selection.aspectRatio = isSquare.value ? 1 : NaN;
        selection.initialAspectRatio = isSquare.value ? 1 : NaN;

        selection.$render();
    }

    async function initCropper() {
        destroyCropper();

        if( !props.src || !containerRef.value ) return;

        const imageSrc = resolveSrc(props.src);

        if( !imageSrc ) return;

        if( props.src instanceof File || props.src instanceof Blob ) {
            objectUrl = imageSrc;
        }

        const image = new Image();

        image.src = imageSrc;

        cropper = new Cropper(image, {
            container: containerRef.value,
        });

        try {
            await cropper.getCropperImage()?.$ready();
            applyAspectRatio();
        } catch (e) {
            emit("error", e);
        }
    }

    async function crop() {
        const selection = cropper?.getCropperSelection();

        if( !selection ) {
            throw new Error("Área de recorte indisponível");
        }

        const canvas = await selection.$toCanvas({
            width: props.outputWidth,
            height: props.outputHeight,
        });

        return new Promise((resolve, reject) => {
            canvas.toBlob(
                (blob) => {
                    if( !blob ) {
                        reject(new Error("Erro ao gerar imagem recortada"));
                        return;
                    }

                    resolve(blob);
                },
                props.mimeType,
                props.quality,
            );
        });
    }

    async function cropAsFile(filename = "imagem-recortada.jpg") {
        const blob = await crop();
        return new File([blob], filename, { type: blob.type });
    }

    watch(
        () => props.src,
        () => nextTick(() => initCropper()),
    );

    watch(isSquare, () => applyAspectRatio());

    onMounted(() => {
        initCropper();
    });

    onBeforeUnmount(() => {
        destroyCropper();
    });

    defineExpose({
        crop,
        cropAsFile,
    });
</script>

<style scoped>
    .image-cropper__container {
        width: 100%;
        overflow: hidden;
        border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
        border-radius: 4px;
    }

    .image-cropper__container :deep(cropper-canvas) {
        height: 100%;
    }
</style>
