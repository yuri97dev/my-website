<template>
    <Container :title="pageTitle">
        <div style="max-width: 768px; margin: 0 auto;">
            <Breadcrumb
                :items="breadcrumbs"
            ></Breadcrumb>

            <h1 class="text-primary">{{ post.title }}</h1>

            <div class="d-flex align-center ga-2 text-grey">
                <v-icon icon="mdi-clock"></v-icon>
                Última atualização: {{ formatDate(post.updated_at, "dd/MM/yyyy HH:mm") }}
            </div>

            <v-divider class="my-4"></v-divider>

            <div
                class="post-container"
                @click="handleContentClick"
                v-html="post.content"
            ></div>
        </div>

        <v-fab
            v-if="!!authUser"
            app
            icon="mdi-pencil"
            size="large"
            color="primary"
            @click="handleEditClick"
        ></v-fab>

        <ImageViewerDialog
            ref="imageViewerDialogRef"
        ></ImageViewerDialog>
    </Container>
</template>

<script setup>
    import { ref, computed } from "vue";
    import { usePage } from "@inertiajs/vue3";

    import Container from "@/Components/Container.vue";
    import Breadcrumb from "@/Components/Breadcrumb.vue";
    import ImageViewerDialog from "@/Components/ImageViewerDialog.vue";

    import { formatDate } from "@/Utils/Common";

    const props = defineProps({
        post: {
            type: Object,
            required: true,
        },
    });

    const page = usePage();

    const imageViewerDialogRef = ref(null);

    const authUser = computed(() => page.props?.auth?.user);

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
</script>
