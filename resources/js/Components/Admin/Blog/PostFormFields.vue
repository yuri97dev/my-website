<template>
    <Dialog
        :title="isEditing ? 'Editar Post' : 'Novo Post'"
        no-body
        hide-footer
        scrollable
        fullscreen
        transition="dialog-bottom-transition"
        @close="handleClose"
        v-model="isDialogOpen"
    >
        <v-card class="d-flex flex-column h-100 rounded-t-0 rounded-b-0">
            <Splitpanes
                :horizontal="!lgAndUp"
                class="default-theme overflow-hidden"
            >
                <Pane
                    min-size="30"
                    size="40"
                    class="pa-4"
                    style="background: transparent"
                >
                    <div class="d-flex flex-column ga-4 h-100">
                        <div>
                            <v-tabs inset grow v-model="tab">
                                <v-tab value="header">
                                    Cabeçalho
                                </v-tab>

                                <v-tab value="content">
                                    Conteúdo
                                </v-tab>
                            </v-tabs>
                        </div>

                        <div class="flex-grow-1 overflow-y-hidden">
                            <v-tabs-window class="h-100" v-model="tab">
                                <v-tabs-window-item
                                    value="header"
                                    class="d-flex align-center justify-center h-100"
                                >
                                    <v-card
                                        variant="tonal"
                                        class="w-100 mx-auto"
                                        style="max-width: var(--center-width)"
                                    >
                                        <v-card-text style="max-height: 100%; overflow-y: auto">
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

                                            <v-text-field
                                                label="Thumbnail (URL)"
                                                class="mb-4"
                                                @paste="handlePasteThumbnail"
                                                v-model="form.thumbnail_path"
                                            ></v-text-field>

                                            <v-autocomplete
                                                label="Categoria"
                                                class="mb-4"
                                                item-title="name"
                                                item-value="id"
                                                required
                                                :loading="loading.categories"
                                                :items="categories"
                                                :return-object="false"
                                                v-model="form.category_id"
                                            >
                                                <template #append-inner>
                                                    <v-btn
                                                        size="small"
                                                        icon="mdi-playlist-edit"
                                                        variant="tonal"
                                                        color="primary"
                                                        class="rounded"
                                                        @click.prevent="openCategoryManager"
                                                    ></v-btn>
                                                </template>
                                            </v-autocomplete>

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
                                            >
                                                <template #append-inner>
                                                    <v-btn
                                                        size="small"
                                                        icon="mdi-playlist-edit"
                                                        variant="tonal"
                                                        color="primary"
                                                        class="rounded"
                                                        @click.prevent="openTagManager"
                                                    ></v-btn>
                                                </template>
                                            </v-autocomplete>

                                            <v-textarea
                                                label="Resumo"
                                                class="mb-4"
                                                rows="1"
                                                auto-grow
                                                v-model="form.excerpt"
                                            ></v-textarea>

                                            <v-switch
                                                label="Publicar"
                                                color="primary"
                                                false-value="draft"
                                                true-value="published"
                                                v-model="form.status"
                                            ></v-switch>
                                        </v-card-text>
                                    </v-card>
                                </v-tabs-window-item>

                                <v-tabs-window-item
                                    value="content"
                                    class="h-100"
                                >
                                    <v-card
                                        variant="tonal"
                                        class="bg-surface-light pa-1 h-100"
                                    >
                                        <MonacoEditor
                                            language="html"
                                            class="rounded-lg"
                                            v-model="form.content"
                                        />
                                    </v-card>
                                </v-tabs-window-item>
                            </v-tabs-window>
                        </div>
                    </div>
                </Pane>

                <Pane
                    min-size="30"
                    size="60"
                    class="pa-4"
                    style="background: transparent"
                >
                    <v-card
                        variant="tonal"
                        class="h-100"
                        :loading="loading.post"
                    >
                        <v-card-text style="max-height: 100%; overflow-y: auto">
                            <div v-html="htmlContent"></div>

                            <v-skeleton-loader
                                v-if="loading.post"
                                type="paragraph@3"
                            ></v-skeleton-loader>
                        </v-card-text>
                    </v-card>
                </Pane>
            </Splitpanes>

            <v-divider></v-divider>

            <v-card-actions class="d-flex justify-end">
                <slot name="action-buttons"></slot>

                <v-btn
                    variant="tonal"
                    color="primary"
                    prepend-icon="mdi-content-save"
                    text="Salvar"
                    @click="handleSave"
                ></v-btn>
            </v-card-actions>
        </v-card>
    </Dialog>

    <CategoryManager
        ref="categoryManagerRef"
        @update="getCategories"
    />

    <TagManager
        ref="tagManagerRef"
        @update="getTags"
    />
</template>

<script setup>
    import { ref, computed, nextTick, onMounted, defineAsyncComponent } from "vue";
    import { useDisplay } from "vuetify";

    import { Splitpanes, Pane } from "splitpanes";
    import "splitpanes/dist/splitpanes.css";

    const MonacoEditor = defineAsyncComponent(() => import("@/Components/MonacoEditor.vue"));

    import api from "@/api";

    import Dialog from "@/Components/Dialog.vue";
    import CategoryManager from "@/Components/Admin/Blog/CategoryManager.vue";
    import TagManager from "@/Components/Admin/Blog/TagManager.vue";

    import { alertError, alertSuccess } from "@/Utils/Alert";

    const emit = defineEmits([
        "close",
    ]);

    const { lgAndUp } = useDisplay();

    const isDialogOpen = ref(false);

    const tab = ref("header");

    const categoryManagerRef = ref(null);
    const tagManagerRef = ref(null);

    const form = ref({});
    const post = ref({});
    const categories = ref([]);
    const tags = ref([]);

    const loading = ref({
        post: false,
        categories: false,
        tags: false,
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
            const url = route("api.blog.posts.get-by-id", post.value.id);
            const { data } = await api.get(url);

            const postData = {
                ...data.post,
                tags: data.post.tags?.map(tag => tag.id),
            };

            form.value = postData;
            post.value = postData;
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

    async function updatePost() {
        loading.value.post = true;

        try {
            const url = route("api.blog.posts.update", post.value.id);
            await api.put(url, form.value);

            alertSuccess("Ok", "Post salvo com sucesso");
        } catch (e) {
            console.error(e);
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao salvar post");
        } finally {
            loading.value.post = false;
        }
    }

    async function createPost() {
        loading.value.post = true;

        try {
            const url = route("api.blog.posts.store");
            const { data } = await api.post(url, form.value);

            post.value.id = data.post.id;

            alertSuccess("Ok", "Post criado com sucesso");

            nextTick(async () => {
                await getPost();
            });
        } catch (e) {
            console.error(e);
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao criar post");
        } finally {
            loading.value.post = false;
        }
    }

    async function handleSave() {
        if( isEditing.value ) {
            await updatePost();
        } else {
            await createPost();
        }
    }

    function handlePasteThumbnail(e) {
        const text = e.clipboardData.getData("text");

        const container = document.createElement("div");

        container.innerHTML = text;

        const image = container.querySelector("img");

        if( !!image?.src ) {
            e.preventDefault();
            form.value.thumbnail_path = new URL(image.src).pathname;
        }
    }

    function openCategoryManager() {
        categoryManagerRef.value?.openList();
    }

    function openTagManager() {
        tagManagerRef.value?.openList();
    }

    function open(data = {}) {
        document.documentElement.style.overflowY = "hidden";

        const postData = {
            id: data?.id || null,
            title: data?.title || null,
            slug: data?.slug || null,
            content: data?.content || null,
        };

        post.value = postData;
        form.value = { ...form.value, ...postData };

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
            category_id: null,
            status: "draft",
            content: null,
            excerpt: null,
            meta_description: null,
            thumbnail_path: null,
            tags: [],
        };
    }

    function handleClose() {
        reset();

        document.documentElement.style.overflowY = "scroll";

        nextTick(() => {
            emit("close");
        });
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
