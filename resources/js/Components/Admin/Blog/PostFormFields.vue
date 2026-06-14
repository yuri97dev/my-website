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

                        <v-switch
                            label="Publicar"
                            color="primary"
                            false-value="draft"
                            true-value="published"
                            class="mb-4"
                            v-model="form.status"
                        ></v-switch>

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
                <slot name="action-buttons"></slot>

                <v-btn
                    variant="tonal"
                    color="primary"
                    prepend-icon="mdi-content-save"
                    @click="handleSave"
                >
                    Salvar
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

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
    import { ref, computed, nextTick, onMounted } from "vue";

    import api from "@/api";

    import CategoryManager from "@/Components/Admin/Blog/CategoryManager.vue";
    import TagManager from "@/Components/Admin/Blog/TagManager.vue";

    import { alertError, alertSuccess } from "@/Utils/Alert";

    defineEmits([
        "close",
        "open-images",
    ]);

    const isDialogOpen = ref(false);

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
            const url = route("api.blog.posts.show", post.value.id);
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
            await api.post(url, form.value);

            alertSuccess("Ok", "Post criado com sucesso");
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
