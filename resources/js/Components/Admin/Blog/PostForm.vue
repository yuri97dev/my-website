<template>
    <v-dialog
        scrollable
        fullscreen
        transition="dialog-bottom-transition"
        @after-leave="handleClose"
        v-model="isDialogOpen"
    >
        <v-card rounded="none">
            <v-toolbar>
                <v-toolbar-title>
                    {{ isEditing ? "Editar Post" : "Novo Post" }}
                </v-toolbar-title>

                <v-btn
                    icon="mdi-close"
                    variant="tonal"
                    color="primary"
                    @click="isDialogOpen = false"
                ></v-btn>
            </v-toolbar>

            <v-card-text>
                <v-row>
                    <v-col cols="12" xl="5" lg="6">
                        <v-text-field
                            label="Título"
                            class="mb-4"
                            required
                            v-model="form.title"
                        ></v-text-field>

                        <v-text-field
                            label="Slug"
                            class="mb-4"
                            required
                            v-model="form.slug"
                        ></v-text-field>

                        <v-autocomplete
                            label="Categoria"
                            class="mb-4"
                            item-title="name"
                            item-value="id"
                            required
                            :loading="loading.categories"
                            :items="categories"
                            v-model="form.category"
                        ></v-autocomplete>

                        <v-autocomplete
                            label="Tags"
                            class="mb-4"
                            item-title="name"
                            item-value="id"
                            chips
                            multiple
                            :loading="loading.tags"
                            :items="tags"
                            v-model="form.tags"
                        ></v-autocomplete>

                        <v-textarea
                            label="Conteúdo"
                            required
                            auto-grow
                            v-model="form.content"
                        ></v-textarea>
                    </v-col>

                    <v-col cols="12" xl="7" lg="6">
                        <v-card
                            variant="tonal"
                            :loading="loading.post"
                        >
                            <v-card-text>
                                <div v-html="htmlContent"></div>

                                <v-skeleton-loader
                                    v-if="loading.post"
                                    type="paragraph@3"
                                ></v-skeleton-loader>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>
            </v-card-text>

            <v-divider></v-divider>

            <v-card-actions>
                <v-btn
                    variant="tonal"
                    color="secondary"
                    prepend-icon="mdi-image-multiple"
                    @click="openImageBottomSheet"
                >
                    Imagens
                </v-btn>

                <v-btn
                    variant="tonal"
                    color="primary"
                    prepend-icon="mdi-content-save"
                >
                    Salvar
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <v-bottom-sheet
        inset
        scrollable
        transition="dialog-bottom-transition"
        v-model="isImageBottomSheetOpen"
    >
        <v-card
            height="70dvh"
            min-height="400px"
            class="rounded-b-0"
            :loading="loading.images"
        >
            <v-toolbar>
                <v-toolbar-title>
                    Imagens Disponíveis
                </v-toolbar-title>

                <v-btn
                    icon="mdi-close"
                    variant="tonal"
                    color="primary"
                    @click="isImageBottomSheetOpen = false"
                ></v-btn>
            </v-toolbar>

            <v-card-text>
                <v-row>
                    <template v-if="loading.images">
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
                    variant="tonal"
                    color="primary"
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
        v-model="isConfirmRemoveImageDialogOpen"
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
                    @click="isConfirmRemoveImageDialogOpen = false"
                >
                    Cancelar
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script setup>
    import { ref, computed, nextTick, onMounted } from "vue";

    import api from "@/api";

    import { alertError, alertSuccess } from "@/Utils/Alert";
    import { copyText } from "@/Utils/Common";

    const isDialogOpen = defineModel();

    const isImageBottomSheetOpen = ref(false);
    const isConfirmRemoveImageDialogOpen = ref(false);

    const fileInput = ref(null);

    const form = ref({});
    const post = ref({});
    const categories = ref([]);
    const tags = ref([]);
    const images = ref([]);

    const selectedImage = ref(null);

    const errors = ref({});

    const loading = ref({
        post: false,
        categories: false,
        tags: false,
        images: false,
    });

    const isEditing = computed(() => !!post.value.id);

    const htmlContent = computed(() => {
        const { title, content } = form.value;

        return loading.value.post
            ? `<h1>${title || "Carregando"}</h1>`
            : `<h1>${title || "Sem título"}</h1>${content || "Sem conteúdo"}`;
    });

    async function getPost() {
        loading.value.post = true;

        try {
            const url = route("api.blog.posts.show", post.value.id);
            const { data } = await api.get(url);

            form.value = data.post;
            post.value = data.post;
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar post");
        } finally {
            loading.value.post = false;
        }
    }

    async function getCategories() {
        loading.value.categories = true;

        try {
            const url = route("api.blog.posts.categories.index");
            const { data } = await api.get(url);

            categories.value = data.categories;
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar categorias");
        } finally {
            loading.value.categories = false;
        }
    }

    async function getTags() {
        loading.value.tags = true;

        try {
            const url = route("api.blog.posts.tags.index");
            const { data } = await api.get(url);

            tags.value = data.tags;
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar tags");
        } finally {
            loading.value.tags = false;
        }
    }

    async function getImages() {
        loading.value.images = true;

        try {
            const url = route("api.blog.posts.images.index");

            const params = isEditing.value
                ? { post_id: [ null, post.value.id ] }
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
            loading.value.images = false;
        }
    }

    async function handleFileChange(e) {
        const files = Array.from(e.target.files);

        if( !files.length ) return;

        e.target.value = null;

        loading.value.images = true;

        for(const file of files) {
            const formData = new FormData();

            formData.append("image", file);

            if( isEditing.value ) {
                formData.append("post_id", post.value.id);
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
        isConfirmRemoveImageDialogOpen.value = false;

        loading.value.images = true;

        try {
            const url = route("api.blog.posts.images.destroy", selectedImage.value.id);
            await api.delete(url);

            alertSuccess("Ok", "Imagem removida com sucesso");
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao remover imagem");
        } finally {
            loading.value.images = false;
        }

        await getImages();
    }

    async function copyImage({ id }) {
        const url = route("blog.images.show", id);

        const content = `<div style="text-align: center">
            <img src="${url}" style="max-width: 100%; max-height: 300px;">
        </div>`;

        const { success, error } = await copyText(content);

        if( !success ) {
            alertError("Ooops", error || "Erro ao copiar imagem");
        } else {
            alertSuccess("Ok", "Imagem copiada com sucesso");
        }
    }

    function openImageBottomSheet() {
        isImageBottomSheetOpen.value = true;

        nextTick(async () => {
            await getImages();
        });
    }

    function confirmRemoveImage(image) {
        selectedImage.value = image;
        isConfirmRemoveImageDialogOpen.value = true;
    }

    function open(data = {}) {
        document.documentElement.style.overflowY = "hidden";

        post.value = data;
        form.value = { ...form.value, ...data };

        isDialogOpen.value = true;

        if( !isEditing.value ) return;

        nextTick(async () => {
            await getPost();
        });
    }

    function reset() {
        post.value = {};

        form.value = {
            ...form.value,
            id: null,
            title: null,
            slug: null,
            category: null,
            status: null,
            content: null,
            excerpt: null,
            meta_description: null,
            tags: [],
        };
    }

    function handleClose() {
        reset();
        document.documentElement.style.overflowY = "scroll";
    }

    reset();

    onMounted(async () => {
        await getCategories();
        await getTags();
    });

    defineExpose({
        open,
        reset,
    });
</script>
