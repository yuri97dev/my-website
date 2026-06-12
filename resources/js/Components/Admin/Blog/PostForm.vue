<template>
    <v-dialog
        persistent
        scrollable
        fullscreen
        transition="dialog-bottom-transition"
        @after-leave="handleClose"
        v-model="isOpen"
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
                    @click="isOpen = false"
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
                        <v-card variant="tonal" :loading="loading">
                            <v-card-text>
                                <div v-html="htmlContent"></div>

                                <v-skeleton-loader
                                    v-if="loading"
                                    type="paragraph@3"
                                ></v-skeleton-loader>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>
            </v-card-text>

            <v-divider></v-divider>

            <v-card-actions>
                <v-btn>teste</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script setup>
    import { ref, computed, watch, nextTick, onMounted } from "vue";

    import api from "@/api";

    import { alertError } from "@/Utils/Alert";

    const isOpen = defineModel();

    const form = ref({});
    const post = ref({});
    const categories = ref([]);
    const tags = ref([]);

    const errors = ref({});
    const loading = ref(false);

    const isEditing = computed(() => !!post.value.id);

    const htmlContent = computed(() => {
        const { title, content } = form.value;

        return loading.value
            ? `<h1>${title || "Carregando"}</h1>`
            : `<h1>${title || "Sem título"}</h1>${content || "Sem conteúdo"}`;
    });

    async function getPost() {
        loading.value = true;

        try {
            const url = route("api.blog.posts.show", post.value.id);
            const { data } = await api.get(url);

            form.value = data.post;
            post.value = data.post;
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar post");
        } finally {
            loading.value = false;
        }
    }

    async function getCategories() {
        loading.value = true;

        try {
            const url = route("api.blog.posts.categories.index");
            const { data } = await api.get(url);

            categories.value = data.categories;
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar categorias");
        } finally {
            loading.value = false;
        }
    }

    async function getTags() {
        loading.value = true;

        try {
            const url = route("api.blog.posts.tags.index");
            const { data } = await api.get(url);

            tags.value = data.tags;
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar tags");
        } finally {
            loading.value = false;
        }
    }

    function open(data = {}) {
        document.documentElement.style.overflowY = "hidden";

        post.value = data;
        form.value = { ...form.value, ...data };

        isOpen.value = true;

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
