<template>
    <v-dialog
        max-width="900px"
        height="70vh"
        @after-leave="handleClose"
        v-model="isOpen"
    >
        <v-toolbar class="rounded-t-lg">
            <v-toolbar-title>
                <v-icon icon="mdi-image"></v-icon>
            </v-toolbar-title>

            <v-spacer></v-spacer>

            <v-btn
                icon="mdi-close"
                variant="tonal"
                color="primary"
                @click="isOpen = false"
            ></v-btn>
        </v-toolbar>

        <v-card :loading="loading" class="rounded-t-0">
            <div class="d-flex flex-column h-100 pa-4">
                <v-img
                    v-if="!!src"
                    :src="src"
                    @load="loading = false"
                    @error="handleError"
                ></v-img>
            </div>
        </v-card>
    </v-dialog>
</template>

<script setup>
    import { ref, nextTick } from "vue";

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
