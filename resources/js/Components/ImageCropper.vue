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
        >
            <Cropper
                v-if="imageSrc"
                ref="cropperRef"
                class="cropper"
                :src="imageSrc"
                :stencil-props="stencilProps"
                @error="emit('error', $event)"
                @ready="refresh"
            ></Cropper>
        </div>
    </div>
</template>

<script setup>
    import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue";

    import { Cropper } from "vue-advanced-cropper";
    import "vue-advanced-cropper/dist/style.css";

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
    const cropperRef = ref(null);
    const isSquare = ref(true);
    const imageSrc = ref(null);

    const stencilProps = computed(() => isSquare.value ? { aspectRatio: 1 } : {});

    let objectUrl = null;
    let resizeObserver = null;

    function refresh() {
        nextTick(() => cropperRef.value?.refresh());
    }

    async function crop() {
        await nextTick();

        cropperRef.value?.refresh();

        const canvas = cropperRef.value?.getResult()?.canvas;

        if( !canvas ) {
            throw new Error("Área de recorte indisponível");
        }

        return new Promise((resolve, reject) => {
            canvas.toBlob(
                (blob) => blob
                    ? resolve(blob)
                    : reject(new Error("Erro ao gerar imagem recortada")),
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
        (src) => {
            if( objectUrl ) {
                URL.revokeObjectURL(objectUrl);
                objectUrl = null;
            }

            if( src instanceof File || src instanceof Blob ) {
                objectUrl = URL.createObjectURL(src);
                imageSrc.value = objectUrl;
            } else {
                imageSrc.value = src || null;
            }
        },
        { immediate: true },
    );

    watch(isSquare, refresh);

    onMounted(() => {
        resizeObserver = new ResizeObserver(refresh);
        resizeObserver.observe(containerRef.value);
    });

    onBeforeUnmount(() => {
        resizeObserver?.disconnect();

        if( objectUrl ) {
            URL.revokeObjectURL(objectUrl);
        }
    });

    defineExpose({
        crop,
        cropAsFile,
    });
</script>

<style scoped>
    .cropper-container {
        height: 60dvh;
        min-height: 400px;
        overflow: hidden;
    }

    .cropper {
        height: 100%;
    }
</style>
