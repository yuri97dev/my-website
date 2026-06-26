<template>
    <Dialog
        title="Visualizar Imagem"
        no-body
        hide-footer
        max-width="900px"
        height="70vh"
        @close="handleClose"
        v-model="isOpen"
    >
        <v-card :loading="loading" class="rounded-t-0">
            <div class="d-flex flex-column h-100 pa-4">
                <v-img
                    v-if="!!src"
                    :src="src"
                    class="rounded"
                    @load="loading = false"
                    @error="handleError"
                ></v-img>
            </div>
        </v-card>
    </Dialog>
</template>

<script setup>
    import { ref, nextTick } from "vue";

    import Dialog from "@/Components/Dialog.vue";

    import { toastError } from "@/Utils/Alert";

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
        toastError("Ooops", "Erro ao carregar imagem");
    }

    function handleClose() {
        src.value = null;
    }

    defineExpose({
        open,
    });
</script>
