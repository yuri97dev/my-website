<template>
    <div class="image-cropper">
        <div class="d-flex justify-center pa-2">
            <v-btn-toggle
                divided
                mandatory
                variant="outlined"
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
            class="cropper-container"
            style="height: 60dvh"
        ></div>
    </div>
</template>

<script setup>
    import { ref, watch, onMounted, onBeforeUnmount } from "vue";

    import Cropper from "cropperjs";

    const props = defineProps({
        src: {
            type: [String, File, Blob],
            default: null,
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
    const isSquare = ref(true);

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

        const canvas = await selection.$toCanvas();

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
        () => initCropper(),
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
    .cropper-container :deep(cropper-canvas) {
        height: 100%;
    }
</style>
