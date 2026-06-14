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
</template>

<script setup>
    import { ref, computed, nextTick, onMounted } from "vue";

    import api from "@/api";

    import { alertError, alertSuccess } from "@/Utils/Alert";

    defineEmits([
        "close",
        "open-images",
    ]);

    const isDialogOpen = ref(false);

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

    async function savePost() {
        loading.value.post = true;

        try {
            const url = route("api.blog.posts.update", post.value.id);

            await api.put(url, {
                ...form.value,
                post_category_id: form.value.category?.id,
                tags: (form.value.tags ?? []).map(tag => tag.id),
            });

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

            await api.post(url, {
                ...form.value,
                post_category_id: form.value.category?.id,
                tags: (form.value.tags ?? []).map(tag => tag.id),
            });

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
            await savePost();
        } else {
            await createPost();
        }
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
