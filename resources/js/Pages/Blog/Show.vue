<template>
    <Container :title="pageTitle">
        <div style="max-width: 768px; margin: 0 auto;">
            <Breadcrumb
                :items="breadcrumbs"
            ></Breadcrumb>

            <h1 class="text-primary">{{ post.title }}</h1>

            <div class="d-flex flex-wrap align-center ga-4 text-grey">
                <div
                    v-if="post.published_at"
                    class="d-flex align-center ga-2"
                >
                    <v-icon icon="mdi-calendar"></v-icon>
                    Publicado em: {{ formatDate(post.published_at, "dd/MM/yyyy HH:mm") }}
                </div>

                <div class="d-flex align-center ga-2">
                    <v-icon icon="mdi-clock"></v-icon>
                    Última atualização: {{ formatDate(post.updated_at, "dd/MM/yyyy HH:mm") }}
                </div>
            </div>

            <div
                v-if="post.tags?.length"
                class="d-flex flex-wrap align-center ga-2 mt-3"
            >
                <v-icon
                    icon="mdi-tag-multiple"
                    class="text-grey"
                ></v-icon>

                <v-chip
                    v-for="tag in post.tags"
                    :key="tag.id"
                    color="primary"
                    variant="outlined"
                    size="small"
                >{{ tag.name }}</v-chip>
            </div>

            <v-divider class="my-4"></v-divider>

            <div
                ref="postContainerRef"
                class="post-container"
                @click="handleContentClick"
                v-html="post.content"
            ></div>

            <v-divider></v-divider>

            <SocialLinks></SocialLinks>
        </div>

        <v-fab
            v-if="isSysAdmin && !post.deleted_at"
            app
            icon
            size="large"
            :color="fabOpen ? '' : 'primary'"
        >
            <v-icon>{{ fabOpen ? "mdi-close" : "mdi-dots-vertical" }}</v-icon>

            <v-speed-dial
                location="top center"
                activator="parent"
                v-model="fabOpen"
            >
                <v-btn
                    key="edit"
                    color="secondary"
                    icon
                    @click="handleEditClick"
                >
                    <v-icon size="24">mdi-pencil</v-icon>
                </v-btn>

                <v-btn
                    key="delete"
                    color="error"
                    icon
                    :disabled="deleting"
                    @click="handleDeleteClick"
                >
                    <v-icon size="24">mdi-delete</v-icon>
                </v-btn>
            </v-speed-dial>
        </v-fab>

        <ImageViewerDialog
            ref="imageViewerDialogRef"
        ></ImageViewerDialog>
    </Container>
</template>

<script setup>
    import { ref, computed, watch, nextTick, onMounted, onBeforeUnmount } from "vue";
    import { router, usePage } from "@inertiajs/vue3";

    import Container from "@/Components/Container.vue";
    import Breadcrumb from "@/Components/Breadcrumb.vue";
    import ImageViewerDialog from "@/Components/ImageViewerDialog.vue";
    import SocialLinks from "@/Components/SocialLinks.vue";

    import { formatDate } from "@/Utils/Common";
    import { alertError, confirmDelete, toastSuccess } from "@/Utils/Alert";

    import api from "@/api";

    const props = defineProps({
        post: {
            type: Object,
            required: true,
        },
    });

    const page = usePage();

    const imageViewerDialogRef = ref(null);
    const postContainerRef = ref(null);
    const fabOpen = ref(false);
    const deleting = ref(false);

    const isSysAdmin = computed(() => page.props?.auth?.isSysAdmin);

    const slug = computed(() => props.post.slug);
    const category = computed(() => props.post.category);

    const pageTitle = computed(() => {
        const { title } = props.post;
        return title || "Post não encontrado";
    });

    const editUrl = computed(() => {
        const { id } = props.post;
        return route("admin.blog.posts", { id });
    });

    function wrapTables(root) {
        if( !root ) return;

        root.querySelectorAll("table").forEach((table) => {
            if( table.closest(".overflow-x-auto") ) return;

            const wrapper = document.createElement("div");

            wrapper.className = "overflow-x-auto";
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        });
    }

    const breadcrumbs = computed(() => [
        {
            key: "blog.index",
            title: "Blog",
        },
        {
            key: "blog.index",
            value: {
                category: category.value.slug,
            },
            title: category.value.name,
        },
        {
            key: "blog.show",
            value: {
                category: category.value.slug,
                slug: slug.value,
            },
            title: slug.value,
        },
    ].filter(Boolean));

    function handleContentClick(e) {
        const spoiler = e.target.closest(".post-spoiler");

        if( spoiler ) {
            const isOpen = spoiler.getAttribute("data-open") === "true";
            spoiler.setAttribute("data-open", isOpen ? "false" : "true");
            return;
        }

        if( e.target.tagName === "A" ) {
            e.target.setAttribute("target", "_blank");
            return;
        }

        if( e.target.tagName === "IMG" ) {
            imageViewerDialogRef.value.open(e.target.src);
            return;
        }
    }

    function handleEditClick() {
        window.open(editUrl.value, "_blank");
    }

    async function handleDeleteClick() {
        const { isConfirmed } = await confirmDelete(
            "Excluir Post",
            `O post "${props.post.title}" será removido permanentemente. Essa ação não pode ser desfeita.`,
        );

        if( !isConfirmed ) return;

        deleting.value = true;

        try {
            const url = route("api.blog.posts.destroy", props.post.id);

            await api.delete(url);

            toastSuccess("Post removido com sucesso");

            router.visit(route("blog.index", { category: category.value.slug }));
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao remover post");
        } finally {
            deleting.value = false;
        }
    }

    watch(() => props.post.content, () => {
        nextTick(() => wrapTables(postContainerRef.value));
    });

    let viewTimer = null;

    onMounted(() => {
        wrapTables(postContainerRef.value);

        if( isSysAdmin.value ) return;

        viewTimer = setTimeout(() => {
            const url = route("api.blog.posts.views.store", { post: props.post.id });
            api.post(url).catch(() => {});
        }, 5000);
    });

    onBeforeUnmount(() => {
        if( viewTimer ) clearTimeout(viewTimer);
    });
</script>
