<template>
    <Container :title="pageTitle">
        <div style="max-width: 768px; margin: 0 auto;">
            <Breadcrumb
                :items="breadcrumbs"
            ></Breadcrumb>

            <h1 class="text-primary">{{ post.title }}</h1>

            <div
                class="post-container"
                @click="handleContentClick"
                v-html="post.content"
            ></div>
        </div>

        <ImageViewerDialog
            ref="imageViewerDialogRef"
        ></ImageViewerDialog>
    </Container>
</template>

<script setup>
    import { ref, computed } from "vue";

    import Container from "@/Components/Container.vue";
    import Breadcrumb from "@/Components/Breadcrumb.vue";
    import ImageViewerDialog from "@/Components/ImageViewerDialog.vue";

    const props = defineProps({
        post: {
            type: Object,
            required: true,
        },
    });

    const imageViewerDialogRef = ref(null);

    const slug = computed(() => props.post.slug);
    const category = computed(() => props.post.category);

    const pageTitle = computed(() => {
        const { title } = props.post;
        return title || "Post não encontrado";
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
        if( e.target.tagName === "IMG" ) {
            imageViewerDialogRef.value.open(e.target.src);
        }
    }
</script>
