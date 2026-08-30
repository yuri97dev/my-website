<template>
    <Dialog
        overlay-id="blog-post-form"
        :title="isEditing ? 'Editar Post' : 'Novo Post'"
        no-body
        hide-footer
        scrollable
        fullscreen
        persistent
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
                    size="70"
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

                                <v-tab value="links">
                                    Links
                                </v-tab>
                            </v-tabs>
                        </div>

                        <div class="flex-grow-1 overflow-y-hidden">
                            <v-tabs-window class="h-100" v-model="tab">
                                <v-tabs-window-item
                                    value="header"
                                    class="d-flex align-center justify-center h-100"
                                >
                                    <div class="d-flex flex-column justify-center w-100 h-100">
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
                                                >
                                                    <template #append-inner>
                                                        <v-btn
                                                            size="small"
                                                            icon="mdi-crop"
                                                            variant="tonal"
                                                            color="primary"
                                                            class="rounded"
                                                            :loading="loading.thumbnail"
                                                            @click.prevent="openThumbnailCropper"
                                                        ></v-btn>
                                                    </template>
                                                </v-text-field>

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
                                    </div>
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

                                <v-tabs-window-item
                                    value="links"
                                    class="h-100"
                                >
                                    <v-list
                                        lines="two"
                                        class="bg-surface-light rounded-lg"
                                        style="max-height: 100%; overflow-y: auto"
                                    >
                                        <v-list-item
                                            v-for="(link, index) in links"
                                            :key="`link-${index}`"
                                            :title="link.text"
                                            :subtitle="link.href || link.dataRmHref"
                                            prepend-icon="mdi-link"
                                        >
                                            <template #append>
                                                <v-btn
                                                    v-if="!!link.href"
                                                    size="small"
                                                    icon="mdi-delete"
                                                    variant="tonal"
                                                    color="error"
                                                    class="rounded"
                                                    @click.prevent="handleDeleteLink(link, index)"
                                                ></v-btn>

                                                <v-btn
                                                    v-else-if="!!link.dataRmHref"
                                                    size="small"
                                                    icon="mdi-restore"
                                                    variant="tonal"
                                                    color="success"
                                                    class="rounded"
                                                    @click.prevent="handleRestoreLink(link, index)"
                                                ></v-btn>

                                                <v-btn
                                                    :href="link.href || link.dataRmHref"
                                                    target="_blank"
                                                    size="small"
                                                    icon="mdi-open-in-new"
                                                    variant="tonal"
                                                    color="primary"
                                                    class="rounded ms-2"
                                                ></v-btn>
                                            </template>
                                        </v-list-item>
                                    </v-list>
                                </v-tabs-window-item>
                            </v-tabs-window>
                        </div>
                    </div>
                </Pane>

                <Pane
                    min-size="30"
                    size="30"
                    class="pa-4"
                    style="background: transparent"
                >
                    <v-card
                        variant="tonal"
                        class="h-100"
                        :loading="loading.post"
                    >
                        <v-card-text style="max-height: 100%; overflow-y: auto">
                            <div
                                class="post-container"
                                v-html="htmlContent"
                            ></div>

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

    <ImageCropperDialog
        ref="cropperDialogRef"
        @confirm="handleThumbnailCropConfirm"
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
    import ImageCropperDialog from "@/Components/ImageCropperDialog.vue";

    import { alertError, toastSuccess } from "@/Utils/Alert";
    import { compressImageFile } from "@/Utils/ImageCompression";

    const emit = defineEmits([
        "close",
    ]);

    const { lgAndUp } = useDisplay();

    const isDialogOpen = ref(false);

    const tab = ref("header");

    const categoryManagerRef = ref(null);
    const tagManagerRef = ref(null);
    const cropperDialogRef = ref(null);

    const form = ref({});
    const post = ref({});
    const scrapedArticleId = ref(null);
    const categories = ref([]);
    const tags = ref([]);

    const loading = ref({
        post: false,
        categories: false,
        tags: false,
        thumbnail: false,
    });

    const isEditing = computed(() => !!post.value.id);

    const htmlContent = computed(() => {
        const { title, content } = form.value;

        return loading.value.post
            ? `<h1>${title || "Carregando"}</h1>`
            : `<h1>${title || "Sem título"}</h1>${content || "Sem conteúdo"}`;
    });

    const links = computed(() => {
        const div = document.createElement("div");

        div.innerHTML = htmlContent.value;

        const links = div.querySelectorAll("a");

        const duplicates = {};

        return Array.from(links)
            .map(link => ({
                href: link.getAttribute("href"),
                dataRmHref: link.getAttribute("data-rm-href"),
                text: link.textContent,
            }))
            .filter(link => {
                const key = link.href || link.dataRmHref;

                if( !key ) return false;

                if( duplicates[key] ) return false;

                duplicates[key] = true;

                return true;
            });
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

    function getPostImagePaths() {
        const paths = new Set();

        if( form.value.thumbnail_path ) {
            try {
                paths.add(new URL(form.value.thumbnail_path, window.location.origin).pathname);
            } catch {
                paths.add(form.value.thumbnail_path);
            }
        }

        const div = document.createElement("div");

        div.innerHTML = form.value.content || "";

        div.querySelectorAll("img[src]").forEach(image => {
            try {
                paths.add(new URL(image.src, window.location.origin).pathname);
            } catch {
                paths.add(image.getAttribute("src"));
            }
        });

        return [ ...paths ].filter(Boolean);
    }

    async function associatePostImages(postId) {
        const paths = getPostImagePaths();

        if( !paths.length ) return;

        const url = route("api.blog.posts.images.associate");

        await api.post(url, {
            post_id: postId,
            paths,
        });
    }

    async function updatePost() {
        loading.value.post = true;

        try {
            const url = route("api.blog.posts.update", post.value.id);
            await api.put(url, form.value);

            await associatePostImages(post.value.id);

            toastSuccess("Ok", "Post salvo com sucesso");
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
            const payload = { ...form.value };

            if( scrapedArticleId.value ) {
                payload.scraped_article_id = scrapedArticleId.value;
            }

            const { data } = await api.post(url, payload);

            post.value.id = data.post.id;
            scrapedArticleId.value = null;

            await associatePostImages(data.post.id);

            toastSuccess("Ok", "Post criado com sucesso");

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
        removeDataRmHrefs();

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

    function openThumbnailCropper() {
        if( form.value.thumbnail_path ) {
            cropperDialogRef.value?.open(form.value.thumbnail_path);
            return;
        }

        const input = document.createElement("input");

        input.type = "file";
        input.accept = "image/*";

        input.addEventListener("change", () => {
            const file = input.files?.[0];

            if( file ) {
                cropperDialogRef.value?.open(file);
            }
        });

        input.click();
    }

    async function handleThumbnailCropConfirm(file) {
        loading.value.thumbnail = true;

        const uploadFile = await compressImageFile(file);

        const formData = new FormData();

        formData.append("image", uploadFile);

        if( isEditing.value ) {
            formData.append("post_id", post.value.id);
        }

        try {
            const url = route("api.blog.posts.images.store");
            const { data } = await api.post(url, formData);

            form.value.thumbnail_path = route("blog.images.show", data.image.id, false);

            toastSuccess("Ok", "Thumbnail salva com sucesso");
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao salvar thumbnail");
        } finally {
            loading.value.thumbnail = false;
        }
    }

    function handleDeleteLink(link) {
        const div = document.createElement("div");

        div.innerHTML = form.value.content;

        const linkSelector = `a[href="${link.href}"]`;
        const linkElements = div.querySelectorAll(linkSelector);

        if( !linkElements.length ) return;

        linkElements.forEach(linkElement => {
            linkElement.setAttribute("data-rm-href", linkElement.href);
            linkElement.removeAttribute("href");
        });

        form.value.content = div.innerHTML;
    }

    function handleRestoreLink(link) {
        const div = document.createElement("div");

        div.innerHTML = form.value.content;

        const linkSelector = `a[data-rm-href="${link.dataRmHref}"]`;
        const linkElements = div.querySelectorAll(linkSelector);

        if( !linkElements.length ) return;

        linkElements.forEach(linkElement => {
            linkElement.setAttribute("href", linkElement.getAttribute("data-rm-href"));
            linkElement.removeAttribute("data-rm-href");
        });

        form.value.content = div.innerHTML;
    }

    function removeDataRmHrefs() {
        const div = document.createElement("div");

        div.innerHTML = form.value.content;

        const linkElements = div.querySelectorAll("a[data-rm-href]");

        linkElements.forEach(linkElement => {
            linkElement.replaceWith(...linkElement.childNodes);
        });

        form.value.content = div.innerHTML;
    }

    function openCategoryManager() {
        categoryManagerRef.value?.openList();
    }

    function openTagManager() {
        tagManagerRef.value?.openList();
    }

    function open(data = {}) {
        const postData = {
            id: data?.id || null,
            title: data?.title || null,
            content: data?.content || null,
            thumbnail_path: data?.thumbnail_path || null,
            category_id: null,
            slug: null,
            excerpt: null,
            status: "draft",
            tags: [],
        };

        post.value = postData;
        form.value = { ...form.value, ...postData };
        scrapedArticleId.value = data?.scraped_article_id || null;

        isDialogOpen.value = true;

        if( !isEditing.value ) return;

        nextTick(async () => {
            await getPost();
        });
    }

    function reset() {
        post.value = {};
        scrapedArticleId.value = null;

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
