<template>
    <v-bottom-sheet
        inset
        scrollable
        transition="dialog-bottom-transition"
        v-model="isOpen"
    >
        <v-card
            height="70dvh"
            min-height="400px"
            class="rounded-b-0"
            :loading="loading"
        >
            <v-toolbar>
                <v-toolbar-title>
                    Imagens Disponíveis
                </v-toolbar-title>

                <v-btn
                    icon="mdi-close"
                    variant="tonal"
                    color="primary"
                    @click="isOpen = false"
                ></v-btn>
            </v-toolbar>

            <v-card-text>
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
                                        @click="copyImage(image)"
                                    >
                                        Copiar
                                    </v-btn>
                                </div>

                                <div class="flex-grow-1">
                                    <v-btn
                                        block
                                        variant="tonal"
                                        color="error"
                                        prepend-icon="mdi-delete"
                                        @click="confirmRemoveImage(image)"
                                    >
                                        Remover
                                    </v-btn>
                                </div>
                            </v-card-actions>
                        </v-card>
                    </v-col>
                </v-row>
            </v-card-text>

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

            <v-divider></v-divider>

            <v-card-actions>
                <v-btn
                    prepend-icon="mdi-refresh"
                    @click="getImages"
                >
                    Atualizar
                </v-btn>

                <v-btn
                    variant="tonal"
                    color="primary"
                    prepend-icon="mdi-image-plus"
                    @click="fileInput.click()"
                >
                    Adicionar
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-bottom-sheet>

    <v-dialog
        max-width="400"
        v-model="isConfirmRemoveDialogOpen"
    >
        <v-card>
            <v-card-text>
                Tem certeza que deseja remover a imagem?
            </v-card-text>

            <v-card-actions>
                <v-btn
                    variant="tonal"
                    color="error"
                    @click="removeImage"
                >
                    Remover
                </v-btn>

                <v-btn
                    variant="tonal"
                    color="primary"
                    @click="isConfirmRemoveDialogOpen = false"
                >
                    Cancelar
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script setup>
    import { ref, computed, nextTick } from "vue";

    import api from "@/api";

    import {
        alertError,
        alertSuccess,
        toastError,
        toastSuccess,
    } from "@/Utils/Alert";

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

    async function handleFileChange(e) {
        const files = Array.from(e.target.files);

        if( !files.length ) return;

        e.target.value = null;

        loading.value = true;

        for(const file of files) {
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

    async function removeImage() {
        isConfirmRemoveDialogOpen.value = false;

        loading.value = true;

        try {
            const url = route("api.blog.posts.images.destroy", selectedImage.value.id);
            await api.delete(url);

            alertSuccess("Ok", "Imagem removida com sucesso");
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

        const content = `<div class="blog-img-container rounded">
            <img src="${url}" />
        </div>`;

        const { success, error } = await copyText(content);

        if( !success ) {
            toastError("Ooops", error || "Erro ao copiar imagem");
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

    defineExpose({
        open,
    });
</script>
