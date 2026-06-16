<template>
    <Container :title="pageTitle">
        <div style="max-width: 768px; margin: 0 auto;">
            <Breadcrumb
                :items="breadcrumbs"
            ></Breadcrumb>

            <div v-if="loading" class="d-flex flex-column ga-2">
                <v-skeleton-loader type="heading" class="bg-transparent" />
                <v-skeleton-loader type="paragraph@5" class="bg-transparent" />
            </div>

            <template v-else>
                <h1 class="text-primary">{{ post.title }}</h1>

                <div
                    @click="handleContentClick"
                    v-html="post.content"
                ></div>
            </template>
        </div>

        <ImageViewerDialog
            ref="imageViewerDialogRef"
        ></ImageViewerDialog>
    </Container>
</template>

<script setup>
    import { ref, computed, onMounted } from "vue";

    import Container from "@/Components/Container.vue";
    import Breadcrumb from "@/Components/Breadcrumb.vue";
    import ImageViewerDialog from "@/Components/ImageViewerDialog.vue";

    import api from "@/api";

    import { alertError } from "@/Utils/Alert";

    const imageViewerDialogRef = ref(null);

    const post = ref({});
    const loading = ref(true);

    const slug = computed(() => route().params.slug);

    const pageTitle = computed(() => {
        if( loading.value ) return "Carregando";
        return post.value.title || "Post não encontrado";
    });

    const breadcrumbs = computed(() => [
        {
            key: "blog.index",
            title: "Blog",
        },
        {
            key: "blog.show",
            value: slug.value,
            title: slug.value,
        },
    ].filter(Boolean));

    async function getPost() {
        loading.value = true;

        try {
            const url = route("api.blog.posts.show-by-slug", slug.value);
            const { data } = await api.get(url);

            post.value = data.post;
        } catch (e) {
            const { message } = e?.response?.data || {};
            alertError("Ooops", message || "Erro ao buscar post");
        } finally {
            loading.value = false;
        }
    }

    function handleContentClick(e) {
        if( e.target.tagName === "IMG" ) {
            imageViewerDialogRef.value.open(e.target.src);
        }
    }

    onMounted(async () => {
        await getPost();
    });
</script>
