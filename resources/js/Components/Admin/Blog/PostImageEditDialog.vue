<template>
    <Dialog
        overlay-id="blog-post-image-edit"
        title="Editar Imagem"
        max-width="600"
        max-height="70dvh"
        @close="resetForm"
        v-model="isOpen"
    >
        <v-text-field
            label="URL"
            class="mb-4"
            autofocus
            required
            v-model="form.src"
        ></v-text-field>

        <v-text-field
            label="Texto Alternativo (alt)"
            class="mb-4"
            v-model="form.alt"
        ></v-text-field>

        <template v-if="form.hasFigure">
            <div
                v-for="(caption, index) in form.figcaptions"
                :key="`figcaption-${index}`"
                class="d-flex align-start ga-2 mb-4"
            >
                <v-textarea
                    :label="`Legenda ${index + 1} (figcaption)`"
                    class="flex-grow-1"
                    rows="2"
                    auto-grow
                    v-model="form.figcaptions[index]"
                ></v-textarea>

                <v-btn
                    size="small"
                    icon="mdi-delete"
                    variant="tonal"
                    color="error"
                    class="rounded"
                    @click.prevent="removeFigcaptionField(index)"
                ></v-btn>
            </div>

            <v-btn
                variant="tonal"
                color="primary"
                prepend-icon="mdi-plus"
                text="Adicionar Legenda"
                @click.prevsent="addFigcaptionField"
            ></v-btn>
        </template>

        <template #actions>
            <v-btn
                variant="tonal"
                color="primary"
                prepend-icon="mdi-content-save"
                text="Salvar"
                @click="handleSave"
            ></v-btn>
        </template>
    </Dialog>
</template>

<script setup>
    import { ref } from "vue";

    import Dialog from "@/Components/Dialog.vue";

    const emit = defineEmits([
        "save",
    ]);

    const isOpen = ref(false);
    const editingImage = ref(null);

    const form = ref({
        src: "",
        alt: "",
        figcaptions: [],
        hasFigure: false,
    });

    function open(image) {
        editingImage.value = image;

        form.value = {
            src: image.src || image.dataRmSrc,
            alt: image.alt || "",
            figcaptions: [ ...image.figcaptions ]
                .map(caption => caption.trim())
                .filter(caption => caption !== ""),
            hasFigure: image.hasFigure,
        };

        isOpen.value = true;
    }

    function addFigcaptionField() {
        form.value.figcaptions.push("");
    }

    function removeFigcaptionField(index) {
        form.value.figcaptions.splice(index, 1);
    }

    function resetForm() {
        editingImage.value = null;

        form.value = {
            src: "",
            alt: "",
            figcaptions: [],
            hasFigure: false,
        };
    }

    function handleSave() {
        if( !editingImage.value ) return;

        emit("save", {
            image: editingImage.value,
            src: form.value.src,
            alt: form.value.alt,
            figcaptions: form.value.figcaptions,
            hasFigure: form.value.hasFigure,
        });

        isOpen.value = false;

        resetForm();
    }

    defineExpose({
        open,
    });
</script>
