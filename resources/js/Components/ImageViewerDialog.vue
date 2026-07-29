<template>
    <Dialog
        overlay-id="blog-image-viewer"
        title="Visualizar Imagem"
        no-body
        scrollable
        hide-footer
        max-width="900px"
        @close="handleClose"
        v-model="isOpen"
    >
        <v-card-text>
            <v-img
                v-if="!!src"
                :src="src"
                class="rounded"
                max-height="70dvh"
                min-height="400px"
                @load="loading = false"
                @error="handleError"
            ></v-img>
        </v-card-text>
    </Dialog>
</template>

<script setup>
    import { ref, nextTick } from "vue";

    import Dialog from "@/Components/Dialog.vue";

    import { alertError } from "@/Utils/Alert";

    const isOpen = ref(false);
    const src = ref(null);
    const loading = ref(false);

    function open(imageSrc) {
        loading.value = true;

        isOpen.value = true;

        nextTick(() => {
            src.value = imageSrc;
        });
    }

    function handleError() {
        loading.value = false;
        alertError("Ooops", "Erro ao carregar imagem");
    }

    function handleClose() {
        src.value = null;
    }

    defineExpose({
        open,
    });
</script>
