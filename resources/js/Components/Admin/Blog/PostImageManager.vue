<template>
    <BottomSheet
        title="Imagens Disponíveis"
        :loading="loading"
        v-model="isOpen"
    >
        <v-row>
            <template v-if="loading">
                <v-col
                    v-for="n in 4"
                    :key="n"
                    cols="12"
                    xl="4" lg="6"
                >
                    <v-skeleton-loader
                        type="card"
                    ></v-skeleton-loader>
                </v-col>
            </template>

            <v-col
                v-for="(image, index) in images"
                :key="index"
                cols="12"
                xl="4" lg="6"
            >
                <v-card>
                    <v-img
                        :src="image.path"
                        height="200px"
                        cover
                    ></v-img>

                    <v-card-actions>
                        <div class="flex-grow-1">
                            <v-btn
                                block
                                variant="tonal"
                                color="primary"
                                prepend-icon="mdi-content-copy"
                                text="Copiar"
                                @click="copyImage(image)"
                            ></v-btn>
                        </div>

                        <div class="flex-grow-1">
                            <v-btn
                                block
                                variant="tonal"
                                color="error"
                                prepend-icon="mdi-delete"
                                text="Remover"
                                @click="confirmRemoveImage(image)"
                            ></v-btn>
                        </div>
                    </v-card-actions>
                </v-card>
            </v-col>
        </v-row>

        <v-card-item class="d-none">
            <v-file-input
                ref="fileInput"
                label="Adicionar Imagens"
                multiple
                prepend-icon="mdi-image-plus"
                accept="image/*"
                @change="handleFileChange"
            ></v-file-input>
        </v-card-item>

        <template #actions>
            <v-btn
                prepend-icon="mdi-refresh"
                text="Atualizar"
                @click="getImages"
            ></v-btn>

            <v-btn
                variant="tonal"
                color="primary"
                prepend-icon="mdi-image-plus"
                text="Adicionar"
                @click="fileInput.click()"
            ></v-btn>
        </template>
    </BottomSheet>

    <Dialog
        title="Remover Imagem"
        message="Tem certeza que deseja remover a imagem?"
        max-width="400"
        v-model="isConfirmRemoveDialogOpen"
    >
        <template #actions>
            <v-btn
                variant="tonal"
                color="error"
                prepend-icon="mdi-delete"
                text="Remover"
                @click="removeImage"
            ></v-btn>
        </template>
    </Dialog>
</template>

<script setup>
    import { ref, computed, watch, nextTick, onBeforeUnmount } from "vue";

    import api from "@/api";

    import BottomSheet from "@/Components/BottomSheet.vue";
    import Dialog from "@/Components/Dialog.vue";

    import { alertError, toastSuccess } from "@/Utils/Alert";

    import { copyText } from "@/Utils/Common";

    const isOpen = ref(false);
    const isConfirmRemoveDialogOpen = ref(false);

    const fileInput = ref(null);

    const images = ref([]);
    const selectedImage = ref(null);
    const activePostId = ref(null);

    const loading = ref(false);

    const isEditing = computed(() => !!activePostId.value);

    async function getImages() {
        loading.value = true;

        try {
            const url = route("api.blog.posts.images.index");

            const params = isEditing.value
                ? { post_id: [ null, activePostId.value ] }
                : { available: 1 };

            const { data } = await api.get(url, { params });

            images.value = data.images.map(image => ({
                ...image,
                path: `${route("blog.images.show", image.id)}`,
            }));
        }
        catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar imagens");
        }
        finally {
            loading.value = false;
        }
    }

    async function sendImages(files) {
        loading.value = true;

        const filteredImages = files
            .filter(file => file.type.startsWith("image/"));

        for(const file of filteredImages) {
            const formData = new FormData();

            formData.append("image", file);

            if( isEditing.value ) {
                formData.append("post_id", activePostId.value);
            }

            try {
                const url = route("api.blog.posts.images.store");
                await api.post(url, formData);
            } catch (e) {
                const { message } = e?.response?.data || {};
                alertError("Ooops", message || "Erro ao adicionar imagem");
                break;
            }
        }

        await getImages();
    }

    async function handleFileChange(e) {
        const files = Array.from(e.target.files);

        if( !files.length ) return;

        e.target.value = null;

        await sendImages(files);
    }

    async function handlePasteImage(e) {
        if( !isOpen.value ) return;

        const files = Array.from(e.clipboardData?.items || [])
            .map(item => item.getAsFile())
            .filter(Boolean);

        if( !files.length ) return;

        e.preventDefault();

        await sendImages(files);
    }

    async function removeImage() {
        isConfirmRemoveDialogOpen.value = false;

        loading.value = true;

        try {
            const url = route("api.blog.posts.images.destroy", selectedImage.value.id);
            await api.delete(url);

            toastSuccess("Ok", "Imagem removida com sucesso");
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao remover imagem");
        } finally {
            loading.value = false;
        }

        await getImages();
    }

    async function copyImage({ id }) {
        const url = route("blog.images.show", id, false);

        const content = [
            //`<div class="blog-img-container">`,
            `   <img src="${url}" />`,
            //`</div>`,
        ].join("\n");

        const { success, error } = await copyText(content);

        if( !success ) {
            alertError("Ooops", error || "Erro ao copiar imagem");
        } else {
            toastSuccess("Ok", "Imagem copiada com sucesso");
        }
    }

    function confirmRemoveImage(image) {
        selectedImage.value = image;
        isConfirmRemoveDialogOpen.value = true;
    }

    function open(postId = null) {
        activePostId.value = postId;
        isOpen.value = true;

        nextTick(async () => {
            await getImages();
        });
    }

    watch(isOpen, (open) => {
        if( open ) {
            document.addEventListener("paste", handlePasteImage);
        } else {
            document.removeEventListener("paste", handlePasteImage);
        }
    });

    onBeforeUnmount(() => {
        document.removeEventListener("paste", handlePasteImage);
    });

    defineExpose({
        open,
    });
</script>
